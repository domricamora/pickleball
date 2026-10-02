import { useState } from 'react';
import { ChevronDown } from 'lucide-react';

export interface FaqItem {
    question: string;
    answer: string;
}

interface FaqProps {
    items: FaqItem[];
}

/**
 * Accessible accordion FAQ. Answers are present in the DOM for SEO and are
 * toggleable without JavaScript re-rendering surprises.
 */
export default function Faq({ items }: FaqProps) {
    const [openIndex, setOpenIndex] = useState<number | null>(0);

    return (
        <div className="divide-hairline divide-y">
            {items.map((item, index) => {
                const isOpen = openIndex === index;
                const panelId = `faq-panel-${index}`;
                const buttonId = `faq-button-${index}`;

                return (
                    <div key={item.question} className="border-hairline border-b last:border-b-0">
                        <h3>
                            <button
                                type="button"
                                id={buttonId}
                                aria-expanded={isOpen}
                                aria-controls={panelId}
                                onClick={() => setOpenIndex(isOpen ? null : index)}
                                className="flex w-full items-center justify-between gap-4 py-5 text-left"
                            >
                                <span className="text-pickle-900 font-semibold">{item.question}</span>
                                <ChevronDown
                                    size={20}
                                    aria-hidden
                                    className={`text-pickle-500 shrink-0 transition-transform ${isOpen ? 'rotate-180' : ''}`}
                                />
                            </button>
                        </h3>
                        <div id={panelId} role="region" aria-labelledby={buttonId} hidden={!isOpen} className="pb-5">
                            <p className="text-slate">{item.answer}</p>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
