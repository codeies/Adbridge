<?php


use Carbon_Fields\Field\Field;
use Carbon_Fields\Datastore\Datastore;

/**
 * Stores serialized values in the database
 */
class Adrental_Serialized_Post_Meta_Datastore extends Datastore
{

    /**
     * Initialization tasks for concrete datastores.
     **/
    public function init() {}

    protected function get_key_for_field(Field $field)
    {
        $key = '_' . $field->get_base_name();
        return $key;
    }

    /**
     * Save a single key-value pair to the database with autoload
     *
     * @param string $key
     * @param string $value
     * @param bool $autoload
     */
    protected function save_key_value_pair_with_autoload($key, $value, $autoload)
    {
        global $post;

        $notoptions = wp_cache_get('notoptions', 'options');
        $notoptions[$key] = '';
        wp_cache_set('notoptions', $notoptions, 'options');
        $autoload = $autoload ? 'yes' : 'no';

        if (!add_post_meta($post->ID, $key, $value)) {
            update_post_meta($post->ID, $key, $value);
        }
    }

    /**
     * Load the field value(s)
     *
     * @param Field $field The field to load value(s) in.
     * @return array
     */
    public function load(Field $field)
    {
        global $post;
        $key = $this->get_key_for_field($field);
        $value = get_post_meta($post->ID, $key, true);
        if (empty($value))
            return [];
        if (is_array($value))
            return $value;
        else
            return unserialize($value);
    }

    /**
     * Save the field value(s)
     *
     * @param Field $field The field to save.
     */
    public function save(Field $field)
    {
        if (!empty($field->get_hierarchy())) {
            return; // only applicable to root fields
        }
        $key = $this->get_key_for_field($field);
        $value = $field->get_full_value();
        if (is_a($field, '\\Carbon_Fields\\Field\\Complex_Field')) {
            $value = $field->get_value_tree();
        }
        $this->save_key_value_pair_with_autoload($key, $value, $field->get_autoload());
    }

    /**
     * Delete the field value(s)
     *
     * @param Field $field The field to delete.
     */
    public function delete(Field $field)
    {
        global $post;
        if (!empty($field->get_hierarchy())) {
            return; // only applicable to root fields
        }
        $key = $this->get_key_for_field($field);
        delete_post_meta($post->ID, $key);
    }
}
