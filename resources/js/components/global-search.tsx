import { useState, useEffect } from 'react';
import { router, useHttp } from '@inertiajs/react';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Building2, Search, User, Briefcase } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface SearchResult {
    type: 'contact' | 'company' | 'opportunity';
    id: string;
    title: string;
    subtitle: string | null;
    meta: string | null;
    url: string;
}

interface SearchResponse {
    query: string;
    results: SearchResult[];
}

export function GlobalSearch() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [history, setHistory] = useState<string[]>([]);

    const http = useHttp<SearchResponse>({ headers: { 'Accept': 'application/json' } });

    useEffect(() => {
        const down = (e: KeyboardEvent) => {
            if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                setOpen((open) => !open);
            }
        };

        document.addEventListener('keydown', down);
        return () => document.removeEventListener('keydown', down);
    }, []);

    useEffect(() => {
        if (query.length < 2) {
            setResults([]);
            return;
        }

        const timer = setTimeout(() => {
            http.get('/search', {
                data: { q: query },
                onSuccess: (response) => {
                    setResults(response.results || []);
                    // add to history
                    setHistory(prev => {
                        const newHistory = [query, ...prev.filter(q => q !== query)];
                        return newHistory.slice(0, 10);
                    });
                },
                onError: () => {
                    setResults([]);
                },
            });
        }, 300);

        return () => clearTimeout(timer);
    }, [query]);

    const handleSelect = (url: string) => {
        setOpen(false);
        setQuery('');
        setResults([]);
        router.visit(url);
    };

    const getIcon = (type: string) => {
        switch (type) {
            case 'contact':
                return <User className="size-4" />;
            case 'company':
                return <Building2 className="size-4" />;
            case 'opportunity':
                return <Briefcase className="size-4" />;
            default:
                return <Search className="size-4" />;
        }
    };

    return (
        <>
            <Button
                variant="outline"
                className="relative h-9 w-full justify-start text-sm text-muted-foreground sm:w-64 lg:w-80"
                onClick={() => setOpen(true)}
            >
                <Search className="mr-2 size-4" />
                <span>Search...</span>
                <kbd className="pointer-events-none absolute right-2 top-2 hidden h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium opacity-100 sm:flex">
                    <span className="text-xs">⌘</span>K
                </kbd>
            </Button>

            <CommandDialog open={open} onOpenChange={setOpen}>
                <CommandInput
                    placeholder="Search contacts, companies, opportunities..."
                    value={query}
                    onValueChange={setQuery}
                />
                <CommandList>
                    {!http.processing && query.length >= 2 && results.length === 0 && (
                        <CommandEmpty>No results found.</CommandEmpty>
                    )}

                    {http.processing && query.length >= 2 && (
                        <CommandEmpty>Searching...</CommandEmpty>
                    )}

                    {results.length > 0 && (
                        <CommandGroup heading="Results">
                            {results.map((result) => (
                                <CommandItem
                                    key={`${result.type}-${result.id}`}
                                    value={`${result.type}-${result.id}`}
                                    onSelect={() => handleSelect(result.url)}
                                >
                                    {getIcon(result.type)}
                                    <div className="ml-2 flex flex-col">
                                        <span className="font-medium">{result.title}</span>
                                        {result.subtitle && (
                                            <span className="text-xs text-muted-foreground">
                                                {result.subtitle}
                                                {result.meta && ` · ${result.meta}`}
                                            </span>
                                        )}
                                    </div>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    )}
                </CommandList>
            </CommandDialog>
        </>
    );
}
