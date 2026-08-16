import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { type FormEvent } from 'react';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Field, FieldLabel } from '@/components/ui/field';
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { CircleAlert, CircleCheck, MessageCircleMore } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My Post',
        href: '/my_post',
    },
];

export default function Dashboard() {
    const { data, setData, post, processing, errors } = useForm({
        post_title: '',
        post_content: '',
    });

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        // console.log(data);
        post(route('my-post.store'));
    };

    interface Posts {
        post_title: string;
        post_content: string;
    }

    interface PageProps {
        [key: string]: unknown;

        flash: {
            message?: string;
        };

        posts?: Posts[];
    }

    const { flash, posts } = usePage<PageProps>().props;
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Post" />

            <div className="h-full bg-cyan-200 p-6">
                <div className="flex items-center justify-between bg-gray-700">
                    <h4 className="text-4xl font-bold">My Post</h4>

                    {/* Display Errors */}
                    {Object.keys(errors).length > 0 && (
                        <Alert>
                            <CircleAlert />
                            <AlertTitle>Heads up!</AlertTitle>
                            <AlertDescription>You can add components and dependencies to your app using the cli.</AlertDescription>
                        </Alert>
                    )}

                    {/* Display Success Alert */}
                    {flash.message && (
                        <Alert>
                            <CircleCheck />
                            <AlertTitle>Success</AlertTitle>
                            <AlertDescription>{flash.message}</AlertDescription>
                        </Alert>
                    )}

                    <Dialog>
                        <DialogTrigger>
                            <Button>Create New Post</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <form onSubmit={handleSubmit}>
                                <DialogHeader>
                                    <DialogTitle>New Post</DialogTitle>
                                    <Field className="mt-5 w-full">
                                        <FieldLabel htmlFor="inline-start-input">Title</FieldLabel>
                                        <InputGroup>
                                            <InputGroupInput
                                                id="inline-start-input"
                                                placeholder="Event of my life now."
                                                value={data.post_title}
                                                onChange={(e) => setData('post_title', e.target.value)}
                                            />
                                            <InputGroupAddon align="inline-start">
                                                <MessageCircleMore className="text-muted-foreground" />
                                            </InputGroupAddon>
                                        </InputGroup>
                                        {/* <FieldDescription>Icon positioned at the start.</FieldDescription> */}
                                    </Field>

                                    <Field className="mt-3 w-full">
                                        <FieldLabel htmlFor="inline-start-input">Content</FieldLabel>
                                        <InputGroup>
                                            <InputGroupInput
                                                id="inline-start-input"
                                                placeholder="I play pickle ball."
                                                value={data.post_content}
                                                onChange={(e) => setData('post_content', e.target.value)}
                                            />
                                            <InputGroupAddon align="inline-start">
                                                <MessageCircleMore className="text-muted-foreground" />
                                            </InputGroupAddon>
                                        </InputGroup>
                                        {/* <FieldDescription>Icon positioned at the start.</FieldDescription> */}
                                    </Field>

                                    <Button className="mt-5">Submit</Button>
                                </DialogHeader>
                            </form>
                        </DialogContent>
                    </Dialog>

                    {(posts?.length ?? 0) > 0 && (
                        <Table>
                            <TableCaption>A list of your recent posts.</TableCaption>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[100px]">Invoice</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Method</TableHead>
                                    <TableHead className="text-right">Amount</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="font-medium">INV001</TableCell>
                                    <TableCell>Paid</TableCell>
                                    <TableCell>Credit Card</TableCell>
                                    <TableCell className="text-right">$250.00</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
