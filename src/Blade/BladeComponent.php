<?php


    declare(strict_types = 1);


    namespace WPEmerge\Blade;

    use Illuminate\View\Component as IlluminateComponent;
    use WPEmerge\Contracts\ViewEngineInterface;

    abstract class BladeComponent extends IlluminateComponent
    {

        /**
         * @var ViewEngineInterface
         */
        protected $engine;

        public function setEngine(BladeEngine $engine)
        {

            $this->engine = $engine;

        }

        protected function view(string $view)
        {
            $view = str_replace('components.', '', $view);

            return $this->engine->make('components.'.$view);

        }

    }