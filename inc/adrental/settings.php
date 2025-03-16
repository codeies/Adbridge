<?php


use Carbon_Fields\Container;
use Carbon_Fields\Field;

class AdBridge_Plugin_Options
{
    public function __construct()
    {
        $this->registerOptions();
    }

    public function registerOptions()
    {
        $templateOptions = $this->getTemplatesOptions();

        Container::make('theme_options', __('Settings'))
            ->set_page_parent('edit.php?post_type=campaign')
            ->add_tab(__('WooCommerce Product'), [
                Field::make('select', 'adbridge_wc_product', 'Select WooCommerce Product')
                    ->set_options($this->getWoocommerceProductsOptions()),
            ])
            ->add_tab(__('SMS Settings'), [
                Field::make('text', 'adrental_sms_api_key', 'Routee API Key'),
                Field::make('text', 'adrental_sms_api_secret', 'Routee API Secret'),
            ])
            ->add_tab(__('Email Settings'), [
                Field::make('text', 'adrental_email_from_name', 'From Name')
                    ->set_default_value(get_bloginfo('name')),
                Field::make('text', 'adrental_email_from_address', 'From Email')
                    ->set_default_value(get_option('admin_email')),
                Field::make('rich_text', 'adrental_email_header', 'Email Header')
                    ->set_help_text('Content to appear at the top of all emails'),
                Field::make('rich_text', 'adrental_email_footer', 'Email Footer')
                    ->set_help_text('Content to appear at the bottom of all emails'),
            ])
            ->add_tab(__('Notification System'), $this->getNotificationFields($templateOptions));
    }

    private function getNotificationFields($templateOptions)
    {
        return array_merge(
            [Field::make('separator', 'notification_settings', __('Notification Settings'))],
            $this->getSmsFields($templateOptions),
            $this->getEmailFields($templateOptions)
        );
    }

    private function getSmsFields($templateOptions)
    {
        return [
            Field::make('separator', 'sms_settings', __('SMS Notifications')),
            Field::make('checkbox', 'adrental_enable_sms', __('Enable SMS Notifications')),

            Field::make('complex', 'registration_sms_followups', __('Registration Follow-ups (SMS)'))
                ->add_fields($this->getFollowUpFields($templateOptions))
                ->set_help_text('Add follow-up SMS messages for users who don\'t book campaigns'),

            Field::make('complex', 'campaign_activity_sms', __('Campaign Activity SMS Notifications'))
                ->add_fields($this->getCampaignActivityFields($templateOptions)),

            Field::make('complex', 'abandoned_cart_sms', __('Abandoned Cart SMS Reminders'))
                ->add_fields($this->getReminderFields($templateOptions))
                ->set_max(4)
                ->set_help_text('Max 4 SMS reminders for abandoned carts'),

            //Field::make('separator', 'monthly_reminder_sms', __('Monthly SMS Reminders')),
            //Field::make('checkbox', 'enable_monthly_sms', __('Enable Monthly SMS Reminders')),
            //Field::make('select', 'monthly_sms_template', __('Monthly SMS Template'))->add_options($templateOptions),
        ];
    }

    private function getEmailFields($templateOptions)
    {
        return [
            Field::make('separator', 'email_settings', __('Email Notifications')),
            Field::make('checkbox', 'adrental_enable_email', __('Enable Email Notifications')),

            Field::make('complex', 'registration_email_followups', __('Registration Follow-ups (Email)'))
                ->add_fields($this->getFollowUpFields($templateOptions))
                ->set_help_text('Add follow-up emails for users who don\'t book campaigns'),

            Field::make('complex', 'campaign_activity_email', __('Campaign Activity Email Notifications'))
                ->add_fields($this->getCampaignActivityFields($templateOptions)),

            Field::make('complex', 'abandoned_cart_email', __('Abandoned Cart Email Reminders'))
                ->add_fields($this->getReminderFields($templateOptions))
                ->set_max(4)
                ->set_help_text('Max 4 email reminders for abandoned carts'),

            //Field::make('separator', 'monthly_reminder_email', __('Monthly Email Reminders')),
            //Field::make('checkbox', 'enable_monthly_email', __('Enable Monthly Email Reminders')),
            //Field::make('select', 'monthly_email_template', __('Monthly Email Template'))->add_options($templateOptions),
        ];
    }

    private function getFollowUpFields($templateOptions)
    {
        return [
            Field::make('text', 'delay_hours', __('Delay Hours'))
                ->set_attribute('type', 'number')
                ->set_default_value(24),
            Field::make('select', 'template', __('Template'))
                ->add_options($templateOptions),
            Field::make('select', 'condition', 'Send To')
                ->set_options([
                    'all' => 'All Users',
                    'has_purchase' => 'Users with a Purchase',
                    'no_purchase' => 'Users without a Purchase',
                ])
                ->set_default_value('no_purchase'),
        ];
    }

    private function getCampaignActivityFields($templateOptions)
    {
        return [
            Field::make('select', 'trigger', __('Trigger Event'))
                ->add_options([
                    'booking_confirmation' => 'After Booking Payment',
                    'campaign_start' => 'Campaign Start',
                    'post_campaign' => 'After Campaign End',
                ]),
            Field::make('text', 'delay_hours', __('Delay Hours'))
                ->set_attribute('type', 'number'),
            Field::make('select', 'template', __('Template'))
                ->add_options($templateOptions),
        ];
    }

    private function getReminderFields($templateOptions)
    {
        return [
            Field::make('text', 'delay_hours', __('Delay Hours'))
                ->set_attribute('type', 'number'),
            Field::make('select', 'template', __('Template'))
                ->add_options($templateOptions),
        ];
    }

    private function getWoocommerceProductsOptions()
    {
        $products = get_posts(['post_type' => 'product', 'posts_per_page' => -1]);
        $options = [];
        foreach ($products as $product) {
            $options[$product->ID] = $product->post_title;
        }
        return $options;
    }

    private function getTemplatesOptions()
    {
        $templates = get_posts(['post_type' => 'template', 'posts_per_page' => -1, 'post_status' => 'publish']);
        $options = [];
        foreach ($templates as $template) {
            $options[$template->ID] = $template->post_title;
        }
        return $options;
    }
}
