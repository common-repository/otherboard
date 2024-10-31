<?php
/**
 * Plugin Name:     Otherboard
 * Plugin URI:      https://otherboard.com
 * Description:     Adds improved Yoast, WordPress, and dynamic real-time updates to your Otherboard experience.
 * Author:          JustinSainton, Otherboard
 * Author URI:      https://otherboard.com
 * Text Domain:     otherboard
 * Domain Path:     /languages
 * Version:         0.2.3
 * License:         GPLv2
 * Requires PHP:    7.2
 * Requires at least: 5.7
 * Tested up to:      6.0.2
 *
 * @package         Otherboard
 */

define( 'OTHERBOARD_PLUGIN_VERSION', '0.2.3' );
define( 'OTHERBOARD_ENDPOINT', 'https://app.otherboard.com/api/' );

 add_action( 'init', function() {
    // Yoast by itself does not allow for writing, only reading, of the JSON head data. The plugin can expose the update capability for Yoast metadata.
    register_meta( 'post', '_yoast_wpseo_title', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
    ] );

    register_meta( 'post', '_yoast_wpseo_metadesc', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
    ] );

    add_filter( 'is_protected_meta', function( $protected, $meta_key ) {
        if ( '_yoast_wpseo_title' == $meta_key || '_yoast_wpseo_metadesc' == $meta_key && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            $protected = false;
        }

        return $protected;

    }, 10, 2 );
 } );

function otherboard_get_allowed_post_types() {
    return apply_filters( 'otherboard_allowed_post_types', [ 'post', 'attachment' ] );
}

/**
 * Pushes annotations with user object and contextual data to Otherboard's REST API for Annotations.
 *
 * @param array $data The data to be sent to the API.
 */
function otherboard_push_annotation( $annotation, $type = 'post', $meta = [] ) {

    if ( empty( get_option( 'otherboard_token' ) ) ) {
        return;
    }

    $data = [
        'object' => 'annotation',
        'type' => $type, // post, ID passed in data, or global.
        'data' => array_merge( $annotation, [
            'user' => [
                'id' => get_current_user_id(),
                'name' => get_userdata( get_current_user_id() )->display_name,
                'email' => get_userdata( get_current_user_id() )->user_email,
                'avatar' => get_avatar_url( get_current_user_id() )
            ],
            'meta' => $meta
        ] )
    ];

    return wp_remote_post( OTHERBOARD_ENDPOINT . 'annotations', [
        'body' => $data,
        'blocking' => false,
        'headers' => [
            'Accept' => 'application/json',
            'WP-Plugin-Version' => OTHERBOARD_PLUGIN_VERSION,
            'Authorization' => 'Bearer ' . get_option( 'otherboard_token' ),
        ]
    ] );
}

/**
 * Triggers webhook notifications to Otherboard's Webhook API.
 * otherboard takes these payloads and processes them in different ways.
 *
 * @param array $data The data to be sent to the API.
 */
function otherboard_push_webhook( $eventType, $payload, $meta = [] ) {

    if ( empty( get_option( 'otherboard_token' ) ) ) {
        return;
    }

    $data = array_merge(
        [
            'payload' => $payload
        ],
        [
            'user' => [
                'id' => get_current_user_id(),
                'name' => get_userdata( get_current_user_id() )->display_name,
                'email' => get_userdata( get_current_user_id() )->user_email,
                'avatar' => get_avatar_url( get_current_user_id() )
            ],
            'meta' => $meta
        ]
    );

    $requestBody = [
        'object' => 'event',
        'type' => $eventType,
        'data' => $data
    ];

    return wp_remote_post( OTHERBOARD_ENDPOINT . 'webhooks/wordpress', [
        'body' => $requestBody,
        'blocking' => false,
        'headers' => [
            'WP-Plugin-Version' => OTHERBOARD_PLUGIN_VERSION,
            'Authorization' => 'Bearer ' . get_option( 'otherboard_token' ),
            'Accept' => 'application/json',
        ]
    ] );
}


add_action( 'post_updated', function( $post_ID, $post_after, $post_before ) {

    if ( ! in_array( get_post_type( $post_ID ), otherboard_get_allowed_post_types() ) ) {
        return;
    }

    otherboard_push_annotation(
        [
            'post' => [
                'ID' => $post_ID,
                'before' => $post_before,
                'after'  => $post_after
            ]
        ]
    );

    if ( 'auto-draft' === get_post_status( $post_before ) && 'auto-draft' !== get_post_status( $post_after ) ) {
        otherboard_push_webhook(
            'post.created',
            [
                'post' => $post_after
            ]
        );
    } else {
        otherboard_push_webhook(
            'post.updated',
            [
                'post' => [
                    'ID' => $post_ID,
                    'before' => $post_before,
                    'after'  => $post_after
                ]
            ]
        );
    }

}, 10, 3 );

add_action( 'wp_insert_post', function( $post_ID, $post, $update ) {

    if ( $update ) {
        return;
    }

    if ( ! in_array( get_post_type( $post_ID ), otherboard_get_allowed_post_types() ) ) {
        return;
    }

    if ( get_post_status( $post ) === 'auto-draft' ) {
        return;
    }

    otherboard_push_annotation(
        [
            'post' => $post
        ]
    );

    otherboard_push_webhook(
        'post.created',
        [
            'post' => $post
        ]
    );
}, 10, 3 );

add_action( 'transition_post_status', function ( $new_status, $old_status, $post ) {
    if ( ! in_array( get_post_type( $post ), otherboard_get_allowed_post_types() ) ) {
        return;
    }

    if ( 'auto-draft' === $new_status || 'auto-draft' === $old_status ) {
        return;
    }

    otherboard_push_annotation(
        [
            'post' => $post,
            'status' => [
                'old' => $old_status,
                'new' => $new_status
            ]
        ]
    );

    otherboard_push_webhook(
        'post.status.updated',
        [
            'post' => $post,
            'status' => [
                'old' => $old_status,
                'new' => $new_status
            ]
        ]
    );
}, 10, 3 );

add_action( 'after_delete_post', function ( $post_ID, $post ) {
    if ( ! in_array( $post->post_type, otherboard_get_allowed_post_types() ) ) {
        return;
    }

    otherboard_push_webhook(
        'post.deleted',
        [
            'post' => $post
        ]
    );
}, 20, 2 );

add_action( 'updated_option', function( $option, $old_value, $value ) {

    $allowed_options = apply_filters( 'otherboard_allowed_options_for_webhooks_and_annotations', [
        'blog_public',
        'show_on_front',
        'page_on_front',
        'page_for_posts',
        'admin_email',
        'siteurl',
        'home',
        'users_can_register',
        'blogname',
        'timezone_string',
        'date_format'
    ] );

    if ( ! in_array( $option, $allowed_options, true ) ) {
        return;
    }

    otherboard_push_annotation(
        [
            'option' => [
                'name' => $option,
                'old' => $old_value,
                'new' => $value
            ]
        ],
        'global'
    );
    otherboard_push_webhook(
        'option.updated',
        [
            'option' => [
                'name' => $option,
                'old' => $old_value,
                'new' => $value
            ]
        ],
    );
}, 10, 3 );

add_action( 'upgrader_process_complete', function( $upgrader, $hook_extra ) {
    otherboard_push_annotation([
        'upgrader' => [
            'type' => $hook_extra['type'],
            'action' => $hook_extra['action'],
            'bulk' => $hook_extra['bulk']
        ],
        'global',
        $hook_extra
    ]);

    otherboard_push_webhook(
        $hook_extra['type'] . '.' . $hook_extra['action'],
        [
            'upgrader' => [
                'type' => $hook_extra['type'],
                'action' => $hook_extra['action'],
                'bulk' => $hook_extra['bulk']
            ]
        ],
        $hook_extra
    );
}, 20, 2 );

//Link to Otherboard post in WP List Table and Gutenberg / Classic Editor pages
function otherboard_link_post_column( $columns ) {
    $columns["ob_link"] = "Otherboard URL";
    return $columns;
}

add_filter( 'manage_edit-post_columns', 'otherboard_link_post_column' );

function otherboard_link_post_column_link( $column_name, $post_id ) {
    if ( $column_name == 'ob_link' ) {
        echo '<a href="https://app.otherboard.com/wordpress_post/' . $post_id . '" target="new"><svg style="max-width: 30px; background: #14c57e; padding: 5px; border-radius: 100%;" viewBox="201.462 69.649 195.553 191.469">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M 293.479 168.286 L 269.557 79.004 C 242.621 88.183 221.492 109.662 212.864 137.027 C 203.634 166.304 209.997 198.271 229.726 221.787 C 249.463 245.302 279.838 257.109 310.272 253.103 C 338.716 249.359 363.539 232.285 377.257 207.351 L 293.479 168.286 Z M 385.396 204.538 C 384.549 206.355 383.648 208.139 382.696 209.887 C 368.104 236.675 341.527 255.03 311.054 259.041 C 278.59 263.314 246.191 250.721 225.139 225.637 C 204.094 200.554 197.306 166.455 207.152 135.226 C 216.395 105.909 239.09 82.924 268.003 73.207 C 269.89 72.573 271.803 71.996 273.74 71.477 L 298.54 164.038 L 385.396 204.538 Z" style="fill: rgb(255, 255, 255);"></path>
        <path fill-rule="evenodd" clip-rule="evenodd" d="M 292.418 169.176 L 268.694 80.635 C 242.74 89.92 222.433 110.852 214.061 137.404 C 204.96 166.272 211.234 197.792 230.687 220.98 C 250.149 244.167 280.099 255.809 310.108 251.859 C 337.708 248.226 361.849 231.865 375.501 207.916 L 292.418 169.176 Z M 377.257 207.351 C 377.257 207.351 377.257 207.35 377.257 207.351 C 377.055 207.718 376.849 208.084 376.642 208.449 C 362.806 232.786 338.296 249.414 310.272 253.103 C 279.838 257.109 249.463 245.302 229.726 221.787 C 209.997 198.271 203.634 166.304 212.864 137.027 C 221.365 110.066 242 88.818 268.368 79.419 C 268.763 79.278 269.159 79.14 269.557 79.004 L 293.479 168.286 L 377.257 207.351 Z M 387.065 203.931 L 386.534 205.068 C 385.675 206.91 384.762 208.717 383.798 210.487 C 369.015 237.626 342.09 256.221 311.218 260.286 C 278.329 264.615 245.506 251.856 224.177 226.444 C 202.857 201.032 195.98 166.487 205.955 134.848 C 215.319 105.147 238.312 81.862 267.603 72.018 C 269.514 71.375 271.452 70.79 273.415 70.264 L 274.627 69.939 L 299.602 163.148 L 387.065 203.931 Z M 298.54 164.038 L 273.74 71.477 C 273.335 71.585 272.932 71.696 272.53 71.809 C 271.005 72.239 269.496 72.706 268.003 73.207 C 239.09 82.924 216.395 105.909 207.152 135.226 C 197.306 166.455 204.094 200.554 225.139 225.637 C 246.191 250.721 278.59 263.314 311.054 259.041 C 341.527 255.03 368.104 236.675 382.696 209.887 C 383.449 208.504 384.171 207.099 384.859 205.672 C 385.035 205.308 385.208 204.942 385.379 204.575 C 385.385 204.563 385.391 204.55 385.396 204.538 L 298.54 164.038 Z" style="fill: rgb(255, 255, 255);"></path>
        <path fill-rule="evenodd" clip-rule="evenodd" d="M 369.266 97.646 L 374.871 103.246 C 385.843 114.206 393.171 128.28 395.868 143.553 L 395.868 143.554 C 398.558 158.828 396.493 174.56 389.94 188.617 L 386.582 195.823 L 307.831 159.079 L 369.266 97.646 Z M 320.758 156.802 L 382.939 185.813 L 383.115 185.436 C 389.014 172.781 390.874 158.616 388.452 144.862 M 320.758 156.802 L 369.269 108.294 L 369.549 108.573 C 379.425 118.439 386.023 131.109 388.452 144.861" style="fill: rgb(255, 255, 255);"></path>
        <path d="M 283.643 72.507 L 284.484 75.646 L 288.691 71.302 C 286.984 71.612 285.313 72.06 283.643 72.507 Z M 298.752 69.885 L 286.338 82.566 L 288.392 90.231 L 308.422 69.704 C 305.199 69.591 301.967 69.651 298.745 69.887 L 298.752 69.885 Z M 316.431 70.434 L 290.291 97.32 L 292.344 104.985 L 324.468 71.94 C 321.815 71.295 319.129 70.79 316.428 70.429 L 316.431 70.434 Z M 331.182 73.968 L 294.201 111.911 L 296.255 119.576 L 338.009 76.682 C 335.779 75.652 333.498 74.744 331.173 73.964 L 331.182 73.968 Z M 343.814 79.659 L 298.175 126.657 L 300.229 134.322 L 349.68 83.388 C 347.792 82.042 345.83 80.793 343.805 79.654 L 343.814 79.659 Z M 354.641 87.237 L 302.083 141.328 L 304.131 148.972 L 359.614 91.881 C 358.043 90.24 356.38 88.693 354.641 87.237 Z" style="fill: rgb(255, 255, 255);"></path>
      </svg></a>';
    }
}

add_action( 'manage_posts_custom_column', 'otherboard_link_post_column_link', 10, 2 );

function otherboard_show_ob_link() {
    $post = get_post();
    if ( ! $post ) {
        return;
    }
?>
<div class="misc-pub-section revision-note">
    <label>
        <a href="https://app.otherboard.com/wordpress_post/<?php echo esc_attr( $post->ID ); ?>"><?php _e( 'Otherboard Link', 'revision-notes' ); ?></a>
    </label>
    <p class="description"><?php _e( 'Head over to Otherboard!', 'revision-notes' ); ?></p>
</div>
<?php
}

add_action( 'post_submitbox_misc_actions', 'otherboard_show_ob_link' );

function otherboard_block_assets() {
    wp_enqueue_script(
        'otherboard-block-editor',
        plugin_dir_url( __FILE__ ) . 'index.js',
        [ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-edit-post', 'wp-plugins' ],
        OTHERBOARD_PLUGIN_VERSION
    );
}

add_action( 'enqueue_block_editor_assets', 'otherboard_block_assets' );

function otherboard_register_token_settings() {
    register_setting(
        'general',
        'otherboard_token',
        array(
            'type'              => 'string',
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
        )
    );
}

add_action( 'admin_init',    'otherboard_register_token_settings' );
add_action( 'rest_api_init', 'otherboard_register_token_settings' );

function otherboard_register_settings_fields() {

    add_settings_field(
        'otherboard-access-token',
        __( 'Otherboard Access Token', 'otherboard' ),
        'otherboard_access_token_field',
        'general'
    );
}

add_action( 'admin_init', 'otherboard_register_settings_fields' );

function otherboard_access_token_field() {

    $token = get_option( 'otherboard_token', '' );

    wp_remote_retrieve_response_code( wp_remote_request( OTHERBOARD_ENDPOINT . 'webhooks/wordpress', [
        'method' => 'POST',
        'redirection' => 0,
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'WP-Plugin-Version' => OTHERBOARD_PLUGIN_VERSION
        ]
    ] ) ) === 200 ? $status = 'Connected' : $status = 'Not Connected';

    echo '<input type="text" id="otherboard_token" name="otherboard_token" value="' . esc_attr( $token ) . '" /> <span>' . esc_html( $status ) . '</span>';
}

function otherboard_admin_notice() {
    $token = get_option( 'otherboard_token', '' );

    if ( ! empty( $token ) ) {
        return;
    }

?>
    <div class="notice notice-info">
        <p><?php printf( __( 'The Otherboard Plugin requires an access token in order to work. You can access this information in your Otherboard account. Once you have this information, go to your <a href="%s">General Settings</a> to enter them.', 'otherboard' ), admin_url( 'options-general.php' ) ); ?></p>
    </div>
<?php
}

add_action( 'admin_notices', 'otherboard_admin_notice' );

add_action( 'update_post_metadata', function( $check, $post_ID, $meta_key, $new_value, $old_value ) {

    if ( '_thumbnail_id' !== $meta_key ) {
        return $check;
    }

    if ( $new_value === $old_value ) {
        return $check;
    }

    if ( ! in_array( get_post_type( $post_ID ), otherboard_get_allowed_post_types() ) ) {
        return;
    }

    $post = get_post( $post_ID );
    otherboard_push_annotation(
        [
            'post' => $post,
            'thumbnail' => [
                'old' => wp_get_attachment_image($old_value),
                'new' => wp_get_attachment_image($new_value),
                'attachment_id' => $new_value
            ]
        ]
    );

    otherboard_push_webhook(
        'post.featured_image.updated',
        [
            'post' => $post,
            'attachment_id' => $new_value,
            'thumbnail' => [
                'old' => wp_get_attachment_image($old_value),
                'new' => wp_get_attachment_image($new_value),
            ]
        ]
    );

    return $check;
}, 10, 5  );

add_action( 'wp_insert_comment', function( $id, $comment ) {
    $post = get_post( $comment->comment_post_ID );

    if ( ! in_array( get_post_type( $post ), otherboard_get_allowed_post_types() ) ) {
        return;
    }

    $meta = get_comment_meta( $id );

    otherboard_push_webhook(
        'post.comment.created',
        [
            'post' => $post,
            'comment' => $comment,
            'meta' => $meta,
        ]
    );
}, 10, 2 );

// Handle local updates to Yoast metadata
// Handle Yoast metadata updates wiff tokens