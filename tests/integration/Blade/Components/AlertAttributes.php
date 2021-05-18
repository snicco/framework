<?php


    declare(strict_types = 1);

    namespace Tests\integration\Blade\Components;

    use WPEmerge\Blade\BladeComponent;

    class AlertAttributes extends BladeComponent
    {

        /**
         * The alert type.
         *
         * @var string
         */
        public $type;

        /**
         * The alert message.
         *
         * @var string
         */
        public $message;

        /**
         * Create the component instance.
         *
         * @param  string  $type
         * @param  string  $message
         * @return void
         */
        public function __construct( $type, $message)
        {
            $this->type = $type;
            $this->message = $message;
        }

        public function render()
        {
           return $this->view('components.alert-attributes');
        }



    }