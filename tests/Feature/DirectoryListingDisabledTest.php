<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifikasi directory listing disabled di public/.htaccess (C-03).
 *
 * QW #7 — security hardening: directory listing /storage/ bisa leak
 * struktur order ID + nama file BAST/Order documents.
 *
 * Test ini verify .htaccess content. Behavior live (Apache mod_autoindex
 * menghormati directive) diverifikasi via curl probe eksternal.
 */
class DirectoryListingDisabledTest extends TestCase
{
    public function test_htaccess_disables_directory_listing_via_mod_autoindex(): void
    {
        $htaccess = file_get_contents(public_path('.htaccess'));

        $this->assertNotFalse($htaccess, '.htaccess tidak terbaca');
        $this->assertStringContainsString(
            'Options -Indexes',
            $htaccess,
            '.htaccess harus punya directive "Options -Indexes" untuk disable directory listing.'
        );
    }

    public function test_htaccess_disables_listing_outside_mod_negotiation_block(): void
    {
        // C-03 root cause: directive `Options -Indexes` sebelumnya hanya
        // di dalam <IfModule mod_negotiation.c>. Jika mod_negotiation tidak
        // loaded, Options directive tidak di-apply. Fix: directive ada di
        // top-level <IfModule mod_autoindex.c> block.
        $htaccess = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString(
            '<IfModule mod_autoindex.c>',
            $htaccess,
            'Options -Indexes harus di dalam block mod_autoindex (top-level), bukan mod_negotiation.'
        );
    }

    public function test_htaccess_comment_references_c03_fix(): void
    {
        $htaccess = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString(
            'C-03',
            $htaccess,
            '.htaccess harus ada comment referensi ke C-03 untuk audit trail.'
        );
    }
}
