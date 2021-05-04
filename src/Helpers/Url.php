<?php


	namespace WPEmerge\Helpers;


	class Url {


		public static function combinePath( $before, $new  ) : string {

			return trim( trim( $before , '/' ) . '/' . trim( $new, '/' ), '/' ) ?: '/';

		}

		public static function normalizePath( $url ) : string {


			$trimmed = trim( $url, '\\/' );

			$str = ( $trimmed ) ? '/' . $trimmed . '/' : '/';

			return $str;


		}

		public static function addTrailing( string $url ) : string {

			return rtrim($url, '/') . '/';

		}

	}
