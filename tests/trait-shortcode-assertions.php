<?php

trait ShortcodeAssertions
{
    protected function assertShortcodeExists($tag) {
		$this->assertTrue(shortcode_exists($tag), "Failed asserting that shortcode '$tag' exists.");
	}

    protected function assertShortcodeNotExists($tag) {
		$this->assertFalse(shortcode_exists($tag), "Failed asserting that shortcode '$tag' does not exist.");
	}

	protected function assertShortcodeReturns($expected, $tag, $atts = [], $content = '')
	{
		$actual = $this->callShortcode($tag, $atts, $content);

		$this->assertSame(
			$expected,
			$actual,
			"Failed asserting that [$tag] shortcode response matched expected result."
		);
	}

	protected function callShortcode($tag, $atts = [], $content = '') {
		global $shortcode_tags;

		ob_start();
		$returned = call_user_func($shortcode_tags[$tag], $atts, $content, $tag);
		$STDOUT = ob_get_contents();
		ob_end_clean();

		$this->assertSame('', $STDOUT,
			"Failed asserting that the [$tag] shortcode did not output to STDOUT."
		);

		return $returned;
	}
}
