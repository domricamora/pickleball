import { Head, usePage } from '@inertiajs/react';

interface SeoPayload {
    title: string;
    description: string;
    type?: 'website' | 'article';
    schema?: Record<string, unknown> | Array<Record<string, unknown>>;
    image?: string;
}

interface SeoProps extends Partial<SeoPayload> {
    /** Path or absolute URL. Defaults to the current page. */
    canonical?: string;
    noindex?: boolean;
}

interface CurrentPage {
    url: string;
    props: { seo?: SeoPayload };
    [key: string]: unknown;
}

/**
 * Emits the SEO head for a page: title, description, canonical, Open Graph,
 * Twitter cards and JSON-LD.
 *
 * Values are passed server-side as the `seo` Inertia prop and rendered into
 * `app.blade.php`, so crawlers see them without running JavaScript. Calling
 * `<Seo />` with no props simply re-states those same values after hydration,
 * keeping the two in sync. Explicit props win over the shared payload.
 */
export default function Seo(props: SeoProps = {}) {
    const { url, props: pageProps } = usePage<CurrentPage>().props;

    const seo: SeoPayload = {
        title: 'PicklePlay',
        description: 'Philippine pickleball courts booking platform.',
        type: 'website',
        schema: [],
        ...pageProps.seo,
        ...props,
    };

    const siteName = 'PicklePlay';
    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    const path = props.canonical ?? url;
    const canonicalUrl = path.startsWith('http') ? path : `${origin}${path}`;
    const imageUrl = seo.image ? (seo.image.startsWith('http') ? seo.image : `${origin}${seo.image}`) : undefined;

    const schemas = seo.schema ? (Array.isArray(seo.schema) ? seo.schema : [seo.schema]) : [];

    return (
        <Head>
            <title>{seo.title}</title>
            <meta name="description" content={seo.description} />
            <link rel="canonical" href={canonicalUrl} />

            {props.noindex && <meta name="robots" content="noindex, nofollow" />}

            {/* Open Graph */}
            <meta property="og:site_name" content={siteName} />
            <meta property="og:title" content={seo.title} />
            <meta property="og:description" content={seo.description} />
            <meta property="og:type" content={seo.type ?? 'website'} />
            <meta property="og:url" content={canonicalUrl} />
            <meta property="og:locale" content="en_PH" />
            {imageUrl && <meta property="og:image" content={imageUrl} />}

            {/* Twitter / X */}
            <meta name="twitter:card" content={imageUrl ? 'summary_large_image' : 'summary'} />
            <meta name="twitter:title" content={seo.title} />
            <meta name="twitter:description" content={seo.description} />
            {imageUrl && <meta name="twitter:image" content={imageUrl} />}

            {/* Schema.org */}
            {schemas.map((schema, index) => (
                <script
                    key={index}
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
                />
            ))}
        </Head>
    );
}
