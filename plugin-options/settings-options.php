<?php
/*
 * This file belongs to the YITH framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

return array(

	'settings' => apply_filters( 'yith_wcte_settings_options', array(

			'settings_options_start' => array(
				'type' => 'sectionstart',
				'id'   => 'yith_wcte_settings_options_start'
			),

			'settings_options_title' => array(
				'title' => _x( 'General settings', 'Panel: page title', 'yith-event-tickets-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'yith_wcte_settings_options_title'
			),

			'settings_enable_purchasable_expired' => array(
				'title' => _x( 'Disable sale if expired:', 'Admin option to choose whether to remove events with past dates from sale or not', 'yith-event-tickets-for-woocommerce' ), //@since 1.1.5
				'type'  => 'checkbox',
				'desc'  => _x( 'Choose whether you want to remove events with past dates from sale or not', 'Admin option description', 'yith-event-tickets-for-woocommerce' ), //@since 1.1.5
				'id'    => 'yith_wcte_purchasable_expired'
			),

			'settings_text_expired' => array(
				'title' => _x( 'Text for expired events:', 'Admin option that defines the text used by expired dates', 'yith-event-tickets-for-woocommerce' ),
				//@since 1.1.7
				'type'  => 'text',
				'id'    => 'yith_wcte_text_expired'
			),

			'settings_enable_visible_expired' => array(
				'title' => _x( 'Hide if expired:', 'Admin option to choose whether to hide events with past dates', 'yith-event-tickets-for-woocommerce' ),
				//@since 1.1.5
				'type'  => 'checkbox',
				'desc'  => _x( 'Choose whether you want to hide events with past dates', 'Admin option description', 'yith-event-tickets-for-woocommerce' ),
				//@since 1.1.5
				'id'    => 'yith_wcte_visible_expired'
			),

			'settings_options_end' => array(
				'type' => 'sectionend',
				'id'   => 'yith_wcte_settings_options_end'
			),
		)
	)
);