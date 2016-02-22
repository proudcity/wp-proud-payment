<?php
/*
Plugin Name: Proud Location
Plugin URI: http://proudcity.com/
Description: Declares an Location custom post type.
Depends: wordpress-fields-api
Version: 1.0
Author: ProudCity
Author URI: http://proudcity.com/
License: GPLv2
*/

namespace Proud\Location;

class ProudLocation
{

    public function __construct()
    {
        add_action('init', array($this, 'init') );

        add_action('fields_register', array($this, 'register_fields') );

        add_action('edit_form_after_editor', array( $this, 'output_fields' ) );

        $this->object_type = 'post';
        $this->object_name = 'proud_location';
        $this->id = 'post_' . $this->object_name;
    }

    public function init()
    {
        $labels = array(
            'name' => _x('Locations', 'post type general name', 'fields-api-examples'),
            'singular_name' => _x('Location', 'post type singular name', 'fields-api-examples'),
            'menu_name' => _x('Locations', 'admin menu', 'fields-api-examples'),
            'name_admin_bar' => _x('Location', 'add new on admin bar', 'fields-api-examples'),
            'add_new' => _x('Add New', 'location', 'fields-api-examples'),
            'add_new_item' => __('Add New Location', 'fields-api-examples'),
            'new_item' => __('New Location', 'fields-api-examples'),
            'edit_item' => __('Edit Location', 'fields-api-examples'),
            'view_item' => __('View Location', 'fields-api-examples'),
            'all_items' => __('All Locations', 'fields-api-examples'),
            'search_items' => __('Search Locations', 'fields-api-examples'),
            'parent_item_colon' => __('Parent Locations:', 'fields-api-examples'),
            'not_found' => __('No locations found.', 'fields-api-examples'),
            'not_found_in_trash' => __('No locations found in Trash.', 'fields-api-examples')
        );

        $args = array(
            'labels' => $labels,
            'description' => __('Description.', 'fields-api-examples'),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'location'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'author', 'thumbnail')
        );

        register_post_type($this->object_name, $args);

    }


    public function register_fields( $wp_fields ) {


        $section_id = $this->id . '-my-section';

        $wp_fields->add_section( $this->object_type, $section_id, $this->object_name, array(
            'label'  => __( 'My Section', 'my-text-domain' ),
            'form' => $this->id,
        ) );

        $field_id = 'my-field';
        $field_args = array(
            'control' => array(
                'type'        => 'text',
                'section'     => $section_id,
                'label'       => __( 'My Field', 'my-text-domain' ),
                'description' => __( 'This is a description for My Field.', 'my-text-domain' ),
            ),
        );

        $wp_fields->add_field( $this->object_type, $field_id, $this->object_name, $field_args );

    }

    public function output_fields( $location ) {
        print_r($this->object_type);
        global $wp_fields;

        // Get the form object
        $form = $wp_fields->get_form( $this->object_type, $this->id, $this->object_name );

        // This is the current item ID, like a Post ID, Term ID
        // Should be empty when adding new items
        $item_id = empty($location->ID) ? NULL : $location->ID;

        print_r($form);

        // Render form controls
        echo $form->maybe_render( $item_id, $this->object_name );
    }  
}

new ProudLocation;
