<?php

class CustomPostTypeShortcodesTest extends WP_UnitTestCase
{
    use ShortcodeAssertions;

    /**
	 * @test
	 */
	function it_registers_shortcodes_for_xyz_public_cpt()
	{
		$this->assertShortcodeExists('xyz_url');
		$this->assertShortcodeExists('xyz_link');
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
