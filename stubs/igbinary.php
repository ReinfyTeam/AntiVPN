<?php

declare(strict_types=1);

if (!function_exists("igbinary_serialize")) {
	function igbinary_serialize(mixed $value) : string {
		return serialize($value);
	}
}

if (!function_exists("igbinary_unserialize")) {
	function igbinary_unserialize(string $value) : mixed {
		return unserialize($value);
	}
}
