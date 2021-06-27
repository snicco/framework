<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Listeners;

    use WPEmerge\Auth\Events\SettingAuthCookie;
    use WPEmerge\Session\Session;

    class GenerateNewAuthCookie
    {


        /**
         *  This filters the final return of @see wp_generate_auth_cookie()
         *  The logic is identical. We only replace the random key generated by
         * @see wp_generate_password() with our CSPRNG generated session id.
         */
        public function handleEvent(SettingAuthCookie $event , Session $current_session)
        {
            $user = $event->user;
            $expiration = $event->expiration;
            $scheme = $event->scheme;
            $token = $current_session->getId();

            $pass_frag = substr( $user->user_pass, 8, 4 );

            $key = wp_hash( $user->user_login . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme );

            // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
            $algo = function_exists( 'hash' ) ? 'sha256' : 'sha1';
            $hash = hash_hmac( $algo, $user->user_login . '|' . $expiration . '|' . $token, $key );

            $cookie = $user->user_login . '|' . $expiration . '|' . $token . '|' . $hash;

            return $cookie;

        }

    }