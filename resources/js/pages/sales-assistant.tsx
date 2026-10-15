import { Head, router } from "@inertiajs/react";
import { FormEvent, useRef, useState } from "react";
import AppLayout from "@/layouts/app-layout";

type Message = { role: "user" | "assistant"; content: string };

export default function SalesAssistant({
    documents,
}: {
    documents: { id: number; name: string }[];
}) {
    const [messages, setMessages] = useState<Message[]>([]);
    const [message, setMessage] = useState("");
    const [conversationId, setConversationId] = useState<string | null>(null);
    const [sending, setSending] = useState(false);
    const upload = useRef<HTMLInputElement>(null);

    async function send(event: FormEvent) {
        event.preventDefault();
        const text = message.trim();
        if (!text || sending) return;
        setMessage("");
        setSending(true);
        setMessages((items) => [
            ...items,
            { role: "user", content: text },
            { role: "assistant", content: "" },
        ]);
        const data = new FormData();
        data.append("message", text);
        if (conversationId) data.append("conversation_id", conversationId);
        const response = await fetch("/sales-assistant/chat", {
            method: "POST",
            headers: {
                Accept: "text/event-stream",
                "X-CSRF-TOKEN":
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]',
                    )?.content ?? "",
            },
            body: data,
            credentials: "same-origin",
        });
        if (!response.ok || !response.body) {
            setMessages((items) => [
                ...items.slice(0, -1),
                { role: "assistant", content: "Unable to get a response." },
            ]);
            setSending(false);
            return;
        }
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = "";
        while (true) {
            const { done, value } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });
            const events = buffer.split("\n\n");
            buffer = events.pop() ?? "";
            events.forEach((event) => {
                const dataLine = event
                    .split("\n")
                    .find((line) => line.startsWith("data: "));
                if (!dataLine || dataLine === "data: [DONE]") return;
                try {
                    const payload = JSON.parse(dataLine.slice(6));
                    const chunk = payload.delta ?? payload.text ?? "";
                    if (chunk)
                        setMessages((items) =>
                            items.map((item, i) =>
                                i === items.length - 1
                                    ? { ...item, content: item.content + chunk }
                                    : item,
                            ),
                        );
                } catch {
                    /* Non-text stream events are intentionally ignored. */
                }
            });
        }
        setSending(false);
    }

    return (
        <>
            <Head title="Sales Assistant" />
            <div className="mx-auto flex h-[calc(100vh-8rem)] max-w-4xl flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Sales Assistant</h1>
                    <p className="text-sm text-muted-foreground">
                        Sales reports, inventory, customers, and your uploaded
                        knowledge.
                    </p>
                </div>
                <div className="flex-1 space-y-4 overflow-y-auto rounded-xl border p-4">
                    {messages.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            Ask about sales performance or stock availability.
                        </p>
                    )}
                    {messages.map((item, index) => (
                        <div
                            key={index}
                            className={
                                item.role === "user"
                                    ? "ml-auto max-w-[80%] rounded-lg bg-primary p-3 text-primary-foreground"
                                    : "max-w-[80%] rounded-lg bg-muted p-3"
                            }
                        >
                            {item.content || (sending ? "Thinking…" : "")}
                        </div>
                    ))}
                </div>
                <form onSubmit={send} className="flex gap-2">
                    <input
                        value={message}
                        onChange={(e) => setMessage(e.target.value)}
                        className="min-w-0 flex-1 rounded-md border bg-background px-3 py-2"
                        placeholder="Ask a sales question…"
                    />
                    <button
                        disabled={sending}
                        className="rounded-md bg-primary px-4 py-2 text-primary-foreground disabled:opacity-50"
                    >
                        Send
                    </button>
                </form>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        if (upload.current?.files?.[0])
                            router.post(
                                "/sales-assistant/documents",
                                new FormData(e.currentTarget),
                            );
                    }}
                    className="flex items-center gap-2 text-sm"
                >
                    <input
                        ref={upload}
                        name="document"
                        type="file"
                        accept=".pdf,.txt,.md"
                    />
                    <button className="rounded border px-3 py-1">
                        Index document
                    </button>
                    {documents.length > 0 && (
                        <span className="text-muted-foreground">
                            {documents.length} indexed
                        </span>
                    )}
                </form>
            </div>
        </>
    );
}

SalesAssistant.layout = {
    breadcrumbs: [{ title: "Sales Assistant", href: "/sales-assistant" }],
};
