<?php


	namespace WPEmerge\Contracts;


	interface ResponseFactoryInterface {

		public function view ( string $view, array $data = [], $status = 200, array $headers = []) : ResponseInterface;

	}