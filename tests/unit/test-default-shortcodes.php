<?php
/**
 * Sample test case.
 */
class DefaultShortcodesTest extends WP_UnitTestCase
{
	use ShortcodeAssertions;

	public $attachment_id;

	function setUp()
	{
		parent::setUp();

		$file = realpath(__DIR__ . '/../files/1x1.png');
		$this->attachment_id = $this->factory->attachment->create_upload_object($file);
	}

	/**
	 * @test
	 */
	function it_registers_shortcodes_for_default_types()
	{
		$this->assertShortcodeExists('post_url');
		$this->assertShortcodeExists('post_link');
		$this->assertShortcodeExists('post_src');
		$this->assertShortcodeExists('post_img');

		$this->assertShortcodeExists('page_url');
		$this->assertShortcodeExists('page_link');
		$this->assertShortcodeExists('page_src');
		$this->assertShortcodeExists('page_img');

		$this->assertShortcodeExists('attachment_url');
		$this->assertShortcodeExists('attachment_link');
		$this->assertShortcodeExists('attachment_src');
		$this->assertShortcodeExists('attachment_img');
	}

	/**
	 * @test
	 */
	function the_post_url_shortcode_returns_the_post_permalink()
	{
	    $post_id = $this->factory->post->create();
		$slug = get_post_field('post_name', $post_id);
		$permalink = get_permalink($post_id);

		$this->assertSame($permalink, do_shortcode("[post_url post_id=$post_id]"));
		$this->assertSame($permalink, do_shortcode("[post_url $post_id]"));
		$this->assertSame($permalink, do_shortcode("[post_url slug=$slug]"));
		$this->assertSame($permalink, do_shortcode("[post_url $slug]"));
	}

	/**
	 * @test
	 */
	function the_page_url_shortcode_returns_the_post_permalink()
	{
	    $post_id   = $this->factory->post->create(['post_type' => 'page']);
		$slug      = get_post_field('post_name', $post_id);
		$permalink = get_permalink($post_id);

		$this->assertSame($permalink, do_shortcode("[page_url post_id=$post_id]"));
		$this->assertSame($permalink, do_shortcode("[page_url $post_id]"));
		$this->assertSame($permalink, do_shortcode("[page_url slug=$slug]"));
		$this->assertSame($permalink, do_shortcode("[page_url $slug]"));
	}

	/**
	 * @test
	 */
	function the_attachment_url_shortcode_returns_the_permalink()
	{
		$img_id        = $this->attachment_id;
		$slug          = get_post_field('post_name', $img_id);
		$img_permalink = get_permalink($img_id);

		$this->assertSame($img_permalink, do_shortcode("[attachment_url $img_id]"));
		$this->assertSame($img_permalink, do_shortcode("[attachment_url post_id=$img_id]"));
		$this->assertSame($img_permalink, do_shortcode("[attachment_url slug=$slug]"));
	}

	/**
	 * @test
	 */
	function the_attachment_src_shortcode_returns_the_img_src()
	{
		$img_id  = $this->attachment_id;
		$slug    = get_post_field('post_name', $img_id);
		$img_url = wp_get_attachment_url($img_id);

		$this->assertSame($img_url, do_shortcode("[attachment_src $img_id]"));
		$this->assertSame($img_url, do_shortcode("[attachment_src post_id=$img_id]"));
		$this->assertSame($img_url, do_shortcode("[attachment_src slug=$slug]"));
	}

	/**
	 * @test
	 */
	function the_post_src_attachment_returns_the_url_for_the_featured_image()
	{
	    $post_id = $this->factory->post->create();
		$slug = get_post_field('post_name', $post_id);

		$img_id = $this->attachment_id;

		set_post_thumbnail($post_id, $img_id);
		$this->assertEquals($img_id, get_post_thumbnail_id($post_id));

		$img_url = wp_get_attachment_url($img_id);
		$this->assertSame($img_url, do_shortcode("[post_src $post_id]"));
		$this->assertSame($img_url, do_shortcode("[post_src post_id=$post_id]"));
		$this->assertSame($img_url, do_shortcode("[post_src slug=$slug]"));
	}

}
