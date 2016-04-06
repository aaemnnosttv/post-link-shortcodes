<?php

class CptArchiveShortcodesTest extends WP_UnitTestCase
{
    use ShortcodeAssertions;

    /**
     * @test
     */
    function it_registers_shortcodes_for_post_types_with_has_archive()
    {
        $xyz_pt_obj = get_post_type_object('xyz');
        $this->assertFalse($xyz_pt_obj->has_archive);
        $this->assertShortcodeNotExists('xyz_archive_url');
        $this->assertShortcodeNotExists('xyz_archive_link');

        $dashes_pt_obj = get_post_type_object('cpt-with-dashes');
        $this->assertTrue($dashes_pt_obj->has_archive);

        $this->assertShortcodeExists('cpt-with-dashes_archive_url');
        $this->assertShortcodeExists('cpt-with-dashes_archive_link');

        $_pt_obj = get_post_type_object('cpt_with_underscores');
        $this->assertTrue($_pt_obj->has_archive);

        $this->assertShortcodeExists('cpt_with_underscores_archive_url');
        $this->assertShortcodeExists('cpt_with_underscores_archive_link');
    }

    /**
     * @test
     */
    function the_archive_url_shortcode_returns_the_post_type_archive_url()
    {
        $this->assertShortcodeReturns(
            get_post_type_archive_link('cpt-with-dashes'),
            'cpt-with-dashes_archive_url'
        );

        $this->assertShortcodeReturns(
            get_post_type_archive_link('cpt_with_underscores'),
            'cpt_with_underscores_archive_url'
        );
    }


}
