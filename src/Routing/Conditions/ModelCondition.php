<?php


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteModelResolver;

	class ModelCondition extends UrlCondition {


		/**
		 * @var array
		 */
		private $model_blueprint;

		/**
		 * @var \WPEmerge\Helpers\Handler
		 */
		private $handler;

		/**
		 * @var \WPEmerge\WpdbRouteModelResolver
		 */
		private $model_resolver;

		public function __construct( RouteModelResolver $model_resolver,  $model_blueprint, $handler,  $url,  $where = [] ) {

			parent::__construct( $url, $where );

			$this->model_blueprint = $model_blueprint;
			$this->handler = $handler;
			$this->model_resolver = $model_resolver;

		}

		public function isSatisfied( RequestInterface $request ) : bool {

			if ( ! parent::isSatisfied( $request ) ) {

				return false;

			}

			if ( ! $this->expectsEloquentModels() ) return true;

			return $this->allModelsResolved($request);


		}

		public function getArguments( RequestInterface $request ) {

			$merge = array_merge()

			return $this->models() + parent::getArguments( $request );
		}

		private function allModelsResolved($request) {

			return $this->model_resolver->allModelsCanBeResolved(parent::getArguments($request));

		}

		private function models() {

			return $this->model_resolver->models();

		}

		private function expectsEloquentModels() : bool {

			return $this->model_resolver->expectsEloquent($this->handler);

		}


	}