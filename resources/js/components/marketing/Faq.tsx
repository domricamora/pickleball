import { useState } from 'react';
import { ChevronDown } from 'lucide-react';
import { cx } from '@/lib/format';

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
        <div className="border-hairline-dark border-t">
            {items.map((item, index) => {
                const isOpen = openIndex === index;
                const panelId = `faq-panel-${index}`;
                const buttonId = `faq-button-${index}`;

                return (
                    <div key={item.question} className="border-hairline-dark border-b">
                        <h3>
                            <button
                                type="button"
                                id={buttonId}
                                aria-expanded={isOpen}
                                aria-controls={panelId}
                                onClick={() => setOpenIndex(isOpen ? null : index)}
                                className="group flex w-full items-center justify-between gap-4 py-6 text-left"
                            >
                                <span className="font-display-plain text-mist-50 group-hover:text-lime-accent text-lg transition-colors">
                                    {item.question}
                                </span>
                                <ChevronDown
                                    size={20}
                                    aria-hidden
                                    className={cx(
                                        'shrink-0 transition-transform duration-300',
                                        isOpen ? 'text-lime-accent rotate-180' : 'text-mist-500',
                                    )}
                                />
                            </button>
                        </h3>
                        <div id={panelId} role="region" aria-labelledby={buttonId} hidden={!isOpen} className="pb-6">
                            <p className="text-mist-300 max-w-2xl leading-relaxed">{item.answer}</p>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
