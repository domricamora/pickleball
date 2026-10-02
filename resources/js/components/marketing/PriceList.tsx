import type { ReactNode } from 'react';

export interface PriceListItem {
    icon: ReactNode;
    name: string;
    price: string;
}

interface PriceListProps {
    /** Heading for the panel, e.g. "Equipment rentals". */
    title: string;
    items: PriceListItem[];
}

/**
 * A bordered price panel listing rental items and their peso rates.
 *
 * Rendered as a table because it is tabular data: an assistive technology
 * should be able to associate each price with its item.
 */
export default function PriceList({ title, items }: PriceListProps) {
    return (
        <div className="bg-night-850 border-hairline-dark rounded-panel border p-6">
            <h3 className="font-display-plain text-mist-50 text-lg tracking-wide">{title}</h3>

            <table className="mt-5 w-full text-sm">
                <caption className="sr-only">{title} and prices per session</caption>
                <tbody className="divide-hairline-dark divide-y">
                    {items.map((item) => (
                        <tr key={item.name}>
                            <th scope="row" className="text-mist-200 py-3 text-left font-normal">
                                <span className="flex items-center gap-3">
                                    <span className="text-mist-500" aria-hidden>
                                        {item.icon}
                                    </span>
                                    {item.name}
                                </span>
                            </th>
                            <td className="text-mist-400 py-3 text-right whitespace-nowrap">{item.price}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
