import { useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import outreach from '@/routes/outreach';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Mail, Sparkles } from 'lucide-react';
import type { OutreachAssignment } from '@/types';

interface DraftEmailDrawerProps {
    assignment: OutreachAssignment;
    templates: Array<{
        id: string;
        name: string;
        category: string;
        subject: string;
    }>;
    open: boolean;
    onClose: () => void;
}

export function DraftEmailDrawer({
    assignment,
    templates,
    open,
    onClose,
}: DraftEmailDrawerProps) {
    const [selectedTemplate, setSelectedTemplate] = useState<string>('');
    const [subject, setSubject] = useState('');
    const [body, setBody] = useState('');
    const [toEmail, setToEmail] = useState(assignment.contact?.email ?? '');
    const [isGenerating, setIsGenerating] = useState(false);

    const contact = assignment.contact;

    const generateDraft = () => {
        if (!selectedTemplate) {
            toast.error('Please select a template first');
            return;
        }

        setIsGenerating(true);

        router.post(
            outreach.draftEmail(assignment.id),
            { email_template_id: selectedTemplate },
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    const draft = (page.props.flash as Record<string, unknown>)?.draft as {
                        subject: string;
                        body: string;
                        to_email: string;
                        to_name: string;
                    } | undefined;

                    if (draft) {
                        setSubject(draft.subject);
                        setBody(draft.body);
                        setToEmail(draft.to_email);
                        toast.success('Email draft generated');
                    }
                    setIsGenerating(false);
                },
                onError: () => {
                    toast.error('Failed to generate draft');
                    setIsGenerating(false);
                },
            },
        );
    };

    const copyToClipboard = () => {
        const fullEmail = `To: ${toEmail}\nSubject: ${subject}\n\n${body}`;
        navigator.clipboard.writeText(fullEmail);
        toast.success('Copied to clipboard');
    };

    const markContactedAndClose = () => {
        router.post(
            outreach.markContacted(assignment.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Marked as contacted');
                    onClose();
                },
            },
        );
    };

    return (
        <Sheet open={open} onOpenChange={onClose}>
            <SheetContent side="right" className="w-full sm:max-w-2xl">
                <SheetHeader>
                    <SheetTitle>Draft Email</SheetTitle>
                    <SheetDescription>
                        {contact?.full_name ?? 'Contact'}
                        {contact?.company && ` · ${contact.company.name}`}
                    </SheetDescription>
                </SheetHeader>

                <div className="mt-6 space-y-6">
                    {/* Template selection */}
                    <div className="space-y-2">
                        <Label htmlFor="template">Email Template</Label>
                        <div className="flex gap-2">
                            <Select value={selectedTemplate} onValueChange={setSelectedTemplate}>
                                <SelectTrigger id="template">
                                    <SelectValue placeholder="Select a template" />
                                </SelectTrigger>
                                <SelectContent>
                                    {templates.map((template) => (
                                        <SelectItem key={template.id} value={template.id}>
                                            <div>
                                                <p className="font-medium">{template.name}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {template.subject}
                                                </p>
                                            </div>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button
                                onClick={generateDraft}
                                disabled={!selectedTemplate || isGenerating}
                            >
                                <Sparkles />
                                {isGenerating ? 'Generating...' : 'Generate'}
                            </Button>
                        </div>
                    </div>

                    {/* Email preview */}
                    {(subject || body) && (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="to">To</Label>
                                <Input
                                    id="to"
                                    type="email"
                                    value={toEmail}
                                    onChange={(e) => setToEmail(e.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="subject">Subject</Label>
                                <Input
                                    id="subject"
                                    value={subject}
                                    onChange={(e) => setSubject(e.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="body">Body</Label>
                                <Textarea
                                    id="body"
                                    rows={12}
                                    value={body}
                                    onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setBody(e.target.value)}
                                    className="font-mono text-sm"
                                />
                            </div>

                            <div className="flex gap-2">
                                <Button onClick={copyToClipboard} variant="outline" className="flex-1">
                                    <Mail />
                                    Copy to Clipboard
                                </Button>
                                <Button onClick={markContactedAndClose} className="flex-1">
                                    Mark as Contacted & Close
                                </Button>
                            </div>
                        </>
                    )}

                    {/* Suggested angle */}
                    {assignment.suggested_angle && (
                        <div className="rounded-lg border border-border/60 bg-muted/30 p-3">
                            <p className="text-xs font-medium text-muted-foreground">
                                Suggested Angle
                            </p>
                            <p className="mt-1 text-sm">{assignment.suggested_angle}</p>
                        </div>
                    )}

                    {/* Reasons */}
                    {assignment.reasons && assignment.reasons.length > 0 && (
                        <div className="rounded-lg border border-border/60 bg-muted/30 p-3">
                            <p className="text-xs font-medium text-muted-foreground">
                                Why Now
                            </p>
                            <ul className="mt-2 space-y-1">
                                {assignment.reasons.map((reason, i) => (
                                    <li
                                        key={i}
                                        className="flex items-start gap-1.5 text-sm"
                                    >
                                        <span className="mt-1.5 size-1 shrink-0 rounded-full bg-muted-foreground/40" />
                                        {reason}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}
