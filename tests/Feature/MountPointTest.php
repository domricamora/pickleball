<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guards the mount-point convention (plan.md §4).
 *
 * The app is served at the domain root in production but from a subdirectory
 * under WAMP, so the server shares `basePath` and the front end prefixes
 * root-relative paths with withBasePath().
 *
 * Inertia resolves a request URL with `new URL(href, window.location)`, and a
 * leading slash discards the mount point: form.post('/login') on a page at
 * /p/login is sent to the domain root, where it 404s. The failure is silent --
 * the page simply re-renders with no validation error, which is exactly how it
 * presents to a user trying to sign in.
 *
 * These assertions are static because the bug lives in JavaScript that PHPUnit
 * never executes. A missed call site is a broken page, not a failing route.
 */
class MountPointTest extends TestCase
{
    /**
     * Root-relative URLs that are safe to leave alone.
     *
     * withBasePath() itself, and asset()/href helpers that already run through
     * it (ButtonLink, AdminButton, PageLayout, Footer, Header).
     */
    private const EXEMPT_FILES = [
        'resources/js/lib/format.ts',
        'resources/js/components/ui/Button.tsx',
        'resources/js/components/admin/AdminUi.tsx',
        'resources/js/components/admin/FormFields.tsx',
        'resources/js/components/layout/Header.tsx',
        'resources/js/components/layout/Footer.tsx',
        'resources/js/layouts/PageLayout.tsx',
    ];

    public function test_no_form_or_router_call_uses_a_root_relative_url(): void
    {
        $offenders = [];

        foreach ($this->frontEndFiles() as $file) {
            if (in_array($file, self::EXEMPT_FILES, true)) {
                continue;
            }

            $contents = (string) file_get_contents($file);

            // form.post('/x'), router.get(`/y/${id}`), etc.
            preg_match_all(
                '/\b(?:form|router)\.(?:post|put|patch|delete|get)\(\s*[`\'"]\/[^`\'"]*/',
                $contents,
                $matches
            );

            foreach ($matches[0] as $match) {
                $offenders[] = $file.': '.trim($match);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These calls post to the domain root and break under a subdirectory mount.\n"
            ."Wrap the URL in withBasePath().\n"
            .implode("\n", $offenders)
        );
    }

    public function test_no_raw_href_or_action_uses_a_root_relative_url(): void
    {
        $offenders = [];

        foreach ($this->frontEndFiles() as $file) {
            if (in_array($file, self::EXEMPT_FILES, true)) {
                continue;
            }

            $contents = (string) file_get_contents($file);

            // Only <Link>/<a>/<Form> tags, not props on wrapper components:
            // ButtonLink and AdminButton apply withBasePath() themselves, so
            // `<ButtonLink href="/book">` is already correct.
            preg_match_all('/<(?:Link|a|Form)\b[^>]*?\b(?:href|action)="\/[^"]*"/', $contents, $matches);

            foreach ($matches[0] as $match) {
                $offenders[] = $file.': '.$match;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These navigate to the domain root and break under a subdirectory mount.\n"
            ."Use href={withBasePath('/x')}.\n"
            .implode("\n", $offenders)
        );
    }

    /**
     * Inertia's <Form> silently discards an onSubmit prop.
     *
     * It renders its own handler after spreading props:
     *
     *     createElement('form', { ...props, onSubmit: (e) => { e.preventDefault(); ... } })
     *
     * so the caller's handler never runs. The form then falls back to native
     * browser submission -- action="" and method="get" -- which reloads the page
     * with the credentials in the query string and shows no error at all. Every
     * auth screen presented exactly that way, and PHPUnit cannot see it because
     * the bug is entirely in JavaScript.
     *
     * A plain <form> is correct here: these pages call preventDefault()
     * themselves and then use useForm, which keeps processing state and errors.
     */
    public function test_no_form_relies_on_an_overridden_onsubmit(): void
    {
        $offenders = [];

        foreach ($this->frontEndFiles() as $file) {
            $contents = (string) file_get_contents($file);

            // <Form ... onSubmit={...} /> -- the prop Inertia throws away.
            preg_match_all('/<Form\b[^>]*\bonSubmit\s*=/', $contents, $matches);

            foreach ($matches[0] as $match) {
                $offenders[] = $file.': '.preg_replace('/\s+/', ' ', trim($match));
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Inertia's <Form> overwrites any onSubmit, so these forms silently fall back\n"
            ."to native GET submission and put the password in the URL.\n"
            ."Use a plain <form onSubmit={...}> and call preventDefault() yourself.\n"
            .implode("\n", $offenders)
        );
    }

    /**
     * The server must keep telling the client where it is mounted, otherwise
     * withBasePath() has nothing to prefix with.
     */
    public function test_server_shares_the_mount_point(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('basePath', ''));
    }

    /**
     * Every TS/TSX file under resources/js.
     *
     * @return array<int, string>
     */
    private function frontEndFiles(): array
    {
        $root = dirname(__DIR__, 2).'/resources/js';

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! preg_match('/\.tsx?$/', $file->getFilename())) {
                continue;
            }

            $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen(dirname(__DIR__, 2)) + 1));
        }

        sort($files);

        return $files;
    }
}
