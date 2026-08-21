# Realtime Post Alerts (Laravel Reverb → React)

How a newly created post travels from the database to a live shadcn `<Alert>` on every open
`/my_post` screen — and why the first attempt at rendering that alert could never have worked.

---

## 1. The end-to-end flow

```
POST /my-post/store
        │
        ▼
PostController::store()      validates → Post::create() → Event::dispatch(new NewPostEvent($post))
        │
        ▼
NewPostEvent                 implements ShouldBroadcastNow
                             broadcastOn()   → public channel "post-created"
                             broadcastWith() → the JSON payload
        │
        ▼
Laravel Reverb               relays the frame over websocket
        │
        ▼
My_Post.tsx                  useEchoPublic('post-created', 'NewPostEvent', callback)
                             callback → setAlerts(...) → React re-renders → <Alert> appears
```

**Backend, step by step:**

- [`PostController::store()`](../app/Http/Controllers/PostController.php#L26-L42) validates the
  request, creates the `Post`, and dispatches the event only after the insert succeeds.
- [`NewPostEvent`](../app/Events/NewPostEvent.php) implements `ShouldBroadcastNow` rather than
  `ShouldBroadcast`. That distinction matters operationally: `ShouldBroadcastNow` pushes the frame
  synchronously during the request, so **no queue worker is required**. With plain `ShouldBroadcast`
  the event would sit in the queue until `php artisan queue:work` picked it up, and "realtime" would
  quietly stop being realtime.
- `broadcastOn()` returns `new Channel('post-created')` — a **public** channel. No auth callback, no
  subscriber filtering: anyone with the page open receives it.

**Frontend:**

- [`My_Post.tsx:47`](../resources/js/pages/Posts/My_Post.tsx#L47) subscribes with `useEchoPublic`,
  the public-channel hook from `@laravel/echo-react`. It handles subscribe on mount and unsubscribe
  on unmount for you.

---

## 2. What was broken

The original listener tried to inject the alert by hand:

```tsx
useEchoPublic('post-created', 'NewPostEvent', (event) => {
    console.log(event);

    const responseElement = document.querySelector('#realtime-alert');
    responseElement?.innerHTML += `<Alert>
  <InfoIcon />
  <AlertTitle>New Post Created!</AlertTitle>
  <AlertDescription>
    a new post successfully posted : Title: ${event.post_title} and Content: ${event.post_content}
  </AlertDescription>
</Alert>`;
});
```

This fails for three independent reasons. Any one of them alone would have been enough.

### 2.1 React components are not HTML tags

This is the important one.

`<Alert>`, `<AlertTitle>`, `<InfoIcon />` are **JavaScript functions**. In a `.tsx` file, the JSX
compiler rewrites `<Alert>` into a function call — roughly `React.createElement(Alert, ...)` — which
is why it works in the `return` block of a component.

Inside a **template string** none of that happens. The string is just characters. Assigning it to
`innerHTML` hands those characters to the browser's HTML parser, and the HTML parser has never heard
of a tag named `alert-title`. It doesn't error; it silently creates unknown elements with no
styling, no icon, and none of the shadcn behavior. You get invisible junk in the DOM.

The rule to carry forward: **JSX tag names mean something to React's compiler and to nothing else.**
The moment markup becomes a string, only real HTML tags (`div`, `span`, `p`, …) survive.

### 2.2 The assignment was not valid JavaScript

```js
responseElement?.innerHTML += '...';
```

Optional chaining (`?.`) is not a legal assignment target. You cannot put it on the left-hand side of
`=`, `+=`, or any compound assignment. This is a parse-time error, not a runtime one — the whole
module fails to compile.

The intent was reasonable (skip the write if the element is missing), but the correct spelling would
have been an explicit guard:

```js
if (responseElement) responseElement.innerHTML += '...';
```

### 2.3 It fought React for ownership of the DOM

Suppose both problems above were fixed and the string contained plain `<div>`s. It would _still_ be
fragile.

`#realtime-alert` lived inside a React-rendered tree. React considers that subtree its own: on the
next re-render — a new post arriving via Inertia, the dialog opening, any state change at all —
React reconciles against its virtual DOM, sees no children where it expects none, and wipes the
hand-injected markup. The alerts would appear and then vanish unpredictably.

**Inside a React component, the DOM is React's to manage. You describe what should be there; you do
not reach in and place it.**

---

## 3. The fix

All three problems dissolve into one idea: **let state drive the render.** The websocket callback
records _data_; React turns data into _elements_.

### 3.1 State holds the received events

[`My_Post.tsx:46`](../resources/js/pages/Posts/My_Post.tsx#L46)

```tsx
const [alerts, setAlerts] = useState<{ post_title: string; post_content: string }[]>([]);
```

One array entry per event received. Note the naming: this is the **state** shape, and it uses the
app-wide `post_title` / `post_content` vocabulary — not the shorter names the websocket sends. See
§4 for why those differ.

### 3.2 The listener only appends

[`My_Post.tsx:48-52`](../resources/js/pages/Posts/My_Post.tsx#L48-L52)

```tsx
useEchoPublic<{ title: string; content: string }>('post-created', 'NewPostEvent', (event) => {
    console.log(event);

    setAlerts((prev) => [...prev, { post_title: event.title, post_content: event.content }]);
});
```

The `console.log` is kept deliberately. Printing the raw payload on every broadcast is the quickest
way to see the actual key names the server sent — which is exactly the bug described in §4. Drop it
when the feature stops changing.

Three details worth naming:

- **The updater form `(prev) => [...prev, …]`** is not stylistic. If two broadcasts land in the same
  render cycle, `setAlerts([...alerts, newOne])` would read a stale `alerts` for the second call and
  silently drop the first event. The functional updater always receives the latest state, so rapid
  consecutive posts all survive.

- **The generic `useEchoPublic<{ title: string; content: string }>`** is load-bearing, not
  decoration. Without a type argument the hook types `event` as `unknown`, and TypeScript rejects
  _every_ property access on `unknown` — `event.title` becomes a compile error
  (`TS18046: 'event' is of type 'unknown'`). The generic tells the hook what shape to expect.

- **That generic describes the wire, not the state.** It must mirror the `broadcastWith()` keys —
  which is a different shape from the `useState` generic above. §4 covers this in full; it is the
  single easiest thing to get wrong here.

### 3.3 The render produces real components

[`My_Post.tsx:59-69`](../resources/js/pages/Posts/My_Post.tsx#L59-L69)

```tsx
<div className="mb-4 space-y-2">
    {alerts.map((alert, index) => (
        <Alert key={index}>
            <InfoIcon />
            <AlertTitle>New Post Created!</AlertTitle>
            <AlertDescription>
                a new post successfully posted : Title: {alert.post_title} and Content: {alert.post_content}
            </AlertDescription>
        </Alert>
    ))}
</div>
```

These are genuine components now — the JSX compiler sees them, shadcn's styles apply, the Lucide
icon renders.

The old `<div id="realtime-alert">` placeholder is gone. It existed only as a target for
`querySelector`, and nothing queries for it any more.

### 3.4 Supporting imports

```tsx
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { InfoIcon } from 'lucide-react';
```

Both were referenced by name in the original template string but never actually imported — further
evidence that the string was never going to become components.

---

## 4. The follow-up bug: payload key mismatch (fixed)

Once the alert actually rendered, a second bug became visible. The box, icon, and heading all
appeared correctly — but the values did not:

> a new post successfully posted : Title: and Content:

**Cause:** the two sides disagreed on key names. `broadcastWith()` was sending `title` / `content`,
while the React handler read `post_title` / `post_content`. Both properties were `undefined`, and
`undefined` interpolates into JSX as an empty string.

This is worth dwelling on, because the symptom is misleading. **An empty value looks like a rendering
failure but is a data failure.** The alert component was working perfectly the whole time; it was
faithfully displaying nothing, because nothing is what it was handed. When debugging realtime
features, "the UI appeared but the data is blank" almost always means the payload shape is wrong, not
the component.

The mismatch also **predated** the `innerHTML` rewrite — the original template string read the same
two wrong keys. It was invisible only because that code never rendered anything at all. Fixing one
bug exposed the other.

**Resolution — the frontend was aligned to the backend.**
[`NewPostEvent::broadcastWith()`](../app/Events/NewPostEvent.php#L36-L43) is unchanged and still
emits `title` / `content`. The React handler now reads those exact names.

### The rule: the name you read must match the key that was sent

`'title' => …` in `broadcastWith()` means `event.title` in the handler — character for character.
There is no automatic conversion between the two sides.

### The part that trips people up: two shapes, not one

The listener involves **two independent object shapes**, and they are allowed to differ:

| Shape            | Declared in                    | Must agree with                                      |
| ---------------- | ------------------------------ | ---------------------------------------------------- |
| **Wire payload** | the `useEchoPublic<…>` generic | the `broadcastWith()` keys — `title` / `content`     |
| **State**        | the `useState<…>` generic      | what the JSX reads — `alert.post_title` / `…content` |

Getting `event.title` right but leaving the generic as `{ post_title: string; … }` produces:

```
Property 'title' does not exist on type '{ post_title: string; post_content: string; }'.
```

TypeScript is right. The generic is a **description of what the server sends**, not of what you keep
in state. Both must be declared, and here they describe different things:

```tsx
const [alerts, setAlerts] = useState<{ post_title: string; post_content: string }[]>([]); // state
useEchoPublic<{ title: string; content: string }>(...)                                     // wire
```

### The mapping is deliberate, not redundant

```tsx
setAlerts((prev) => [...prev, { post_title: event.title, post_content: event.content }]);
//                             └─ state naming ─┘         └─ wire naming ─┘
```

This object literal is a **translation layer at the boundary**. It lets the wire stay terse
(`title`) while state and JSX keep the vocabulary used everywhere else in the app — the `posts`
table columns, the validation rules in `PostController::store()`, the Inertia `my_posts` props, the
listeners. Renaming once, at the point of entry, is preferable to letting a second vocabulary leak
through the component.

---

## 5. Verifying it works

Run two processes in separate terminals:

```bash
php artisan reverb:start
npm run dev
```

Then:

1. Open `/my_post` in **two** browser windows.
2. Create a post in one of them.
3. An alert should appear in **both** windows.

That both windows light up — including the author's — is expected, not a bug. `post-created` is a
public channel, and the event is dispatched without `->toOthers()`, so every subscriber receives it.

**When it doesn't work, isolate which hop failed:**

- Reverb's terminal prints the message as it leaves the server → the backend half is fine.
- Browser DevTools → **Network → WS → Messages** shows the arriving frame → the transport is fine.
  This panel shows the **actual key names** on the wire, which is the authoritative answer whenever
  the payload shape is in question — it is how the §4 mismatch was confirmed.
- Frame arrives but nothing renders → the problem is in the React handler.
- Alert renders but values are blank → the frame arrived with different keys than the handler reads.
  Compare the WS panel against the `console.log(event)` output in the listener.

---

## 6. Caveats and follow-ups

**Alerts accumulate without bound.** Nothing ever removes entries from `alerts`. A tab left open all
day keeps every alert it has ever received, growing the array and the DOM indefinitely. The natural
follow-ups are auto-dismiss (a `setTimeout` that filters the entry back out after a few seconds) or
capping the array at the N most recent.

**`key={index}` is safe here — for now.** Index keys are only acceptable when a list is append-only
and never reordered or filtered, which is true today. The moment auto-dismiss is added, entries start
being removed from the middle, indices shift, and React will mismatch alerts to DOM nodes. Switch to
a stable id (`crypto.randomUUID()` at insert time) alongside any dismissal feature.
