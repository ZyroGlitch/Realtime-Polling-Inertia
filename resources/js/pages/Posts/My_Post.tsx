import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage, usePoll } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Field, FieldLabel } from '@/components/ui/field';
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { CircleAlert, CircleCheck, MessageCircleMore } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My Post',
        href: '/my_post',
    },
];

export default function Dashboard({ my_posts }) {
    // console.log(my_posts);

    const { data, setData, post, processing, errors } = useForm({
        post_title: '',
        post_content: '',
    });

    const [open, setOpen] = useState(false); // controls dialog for auto-close on success

    // usePoll(3000, { only: ['my_posts'] }); // refresh posts every 3s

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        console.log(data);
        post(route('my-post.store'), {
            onSuccess: () => setOpen(false), // auto-close dialog
        });
    };

    // const { flash, posts } = usePage<PageProps>().props;
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Post" />

            <div className="h-full p-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl">Posts</h2>
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger>
                            <Button>Create New Post</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <form onSubmit={handleSubmit}>
                                    <DialogTitle>Create New Post</DialogTitle>

                                    <Field className='mt-4'>
                                        <FieldLabel htmlFor="title">Title</FieldLabel>
                                        <Input type='text' id="title" placeholder='New Year 2026' value={data.post_title} onChange={(e) => setData('post_title', e.target.value)} />
                                    </Field>

                                    <Field className='mt-2'>
                                        <FieldLabel htmlFor="content">Content</FieldLabel>
                                        <Textarea id='content' placeholder="Let's Celebrate the new year 2027!" value={data.post_content} onChange={(e) => setData('post_content', e.target.value)} />
                                    </Field>

                                    <Button type='submit' className='w-full mt-6' disabled={processing}>Submit</Button>
                                </form>
                            </DialogHeader>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="grid lg:grid-cols-3 gap-4 mt-6">
                    {my_posts.map(post => (
                        <Card className='min-h-[100px]' key={post.id}>
                            <CardContent className='h-full p-4'>
                                <h4 className='text-2xl font-bold font-mono text-purple-400'>{post.post_title}</h4>
                                <p>{post.post_content}</p>
                            </CardContent>
                        </Card>
                    ))}

                </div>
            </div>
        </AppLayout >
    );
}
