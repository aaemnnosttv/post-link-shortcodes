<?php

class CustomPostTypeShortcodesTest extends WP_UnitTestCase
{
    use ShortcodeAssertions;

    /**
	 * @test
	 */
	function it_registers_shortcodes_for_show_ui_cpts()
	{
		$this->assertShortcodeNotExists('secret_url');
		$this->assertShortcodeNotExists('secret_link');

		$this->assertShortcodeExists('xyz_url');
		$this->assertShortcodeExists('xyz_link');

		$this->assertShortcodeExists('cpt-with-dashes_url');
		$this->assertShortcodeExists('cpt-with-dashes_link');
		$this->assertShortcodeExists('cpt-with-dashes_src');
		$this->assertShortcodeExists('cpt-with-dashes_img');

		$this->assertShortcodeExists('cpt_with_underscores_url');
		$this->assertShortcodeExists('cpt_with_underscores_link');
		$this->assertShortcodeExists('cpt_with_underscores_src');
		$this->assertShortcodeExists('cpt_with_underscores_img');
	}

	/**
	 * @test
	 */
	function it_does_not_register_img_related_shortcodes_if_pt_does_not_support_thumbnails()
	{
		$this->assertFalse(post_type_supports('xyz','thumbnail'));
		$this->assertShortcodeNotExists('xyz_src');
		$this->assertShortcodeNotExists('xyz_img');
	}

}
