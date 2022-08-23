<?php 
/* Plugin name: WordPress Validate Tag Count
Description: Проверка наличия необходимого количества указанного тега в тексте
Author: allmark.sergey@gmail.com */  

add_action('admin_head-post.php','validate_links_count');
add_action('admin_head-post-new.php','validate_links_count');
function validate_links_count()
{
	global $post;
	if(is_admin() && ($post->post_type == 'post' || $post->post_type == 'page'))
	{
		?>
		<script language="javascript" type="text/javascript">
			jQuery(document).ready(() => 
			{
				jQuery('#publish')
					.css('display', 'none')
					.after('<button class="button button-primary button-large" id="validation_submit" type="button"><?php _e("Отправить", "validate_links") ?></button>');
				
				console.log('running this now');
				jQuery('#validation_submit').on('click', (e) =>
				{
					jQuery('#publishing-action .spinner').addClass('is-active');
					const form_data = jQuery('#post').serializeArray();
					const data = {
						action: 'validate_links_pre_submit_validation',
						security: '<?php echo wp_create_nonce( 'pre_publish_validation' ); ?>',
						form_data: jQuery.param(form_data),
					};
					jQuery.post(ajaxurl, data, (response) => 
					{
						if(response.indexOf('true') > -1 || response == true) jQuery('#publish').trigger('click');
						else 
						{
							jQuery('#publishing-action .spinner').removeClass('is-active');
							if(!jQuery('#publishing-action #valid_error').length) jQuery('#publishing-action').append("<div id='valid_error' class='error'></div>");
							jQuery('#publishing-action #valid_error').text('Ошибка: ' + response);
							return false;
						}
					});
				});
			});
		</script>
		<?php
	}
}

add_action('wp_ajax_validate_links_pre_submit_validation', 'validate_links_pre_submit_validation');
function validate_links_pre_submit_validation() 
{
	check_ajax_referer('pre_publish_validation', 'security');
	parse_str($_POST['form_data'], $vars);
	
	$links_quantity = intval(get_option('validate_tag_count__quantity'));    
	if($links_quantity && $links_quantity > 0)
	{
		if(isset($vars['content']) && !empty($vars['content']))
		{
			$quantity = 0;
			$doc = new DOMDocument();
			$doc->loadHTML($vars['content']);
			$links = $doc->getElementsByTagName('a');
			if($links->length == 0)
			{
				printf(esc_html__('На странице должно быть %s внутренних ссылок', 'validate_links'), $links_quantity);
				die();
			}
			elseif($links->length > 0)
			{
				foreach ($links as $a)
				{
					$href = $a->getAttribute('href');
					if($href)
					{
						if(!(substr($href, 0, 4) === "http" || !substr($href, 0, 3) === "://") || strpos($href, parse_url(get_site_url(), PHP_URL_HOST)) !== false)
							$quantity++;
					}
				}
				
				if($quantity < $links_quantity)
				{
					printf(esc_html__('Недостаточно внутренних ссылок. На странице должно быть %s внутренних ссылок (сейчас %s)', 'validate_links'), $links_quantity, $quantity);
					//else printf(esc_html__('Слишком много внутренних ссылок. На странице должно быть %s внутренних ссылок (сейчас %s)', 'validate_links'), $links_quantity, $quantity);
					die();
				}
				
			}
		}
	}
	echo 'true';
	die();
}



add_action('admin_menu', 'validate_tag_count_menu');  
function validate_tag_count_menu()
{
	$page_title = 'WordPress Validate Tag Count';
	$menu_title = 'Validate Tag Count';
	$capability = 'manage_options';
	$menu_slug  = 'validate-tag-count';
	$function   = 'validate_tag_count_page';
	$icon_url   = 'dashicons-media-code';
	$position   = 10;
	add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
	add_action('admin_init', 'update_validate_tag_count');
}

function update_validate_tag_count()
{
	//register_setting('validate-tag-count-settings', 'validate_tag_count__tag');
	register_setting('validate-tag-count-settings', 'validate_tag_count__quantity');
}


if(!function_exists("validate_tag_count_page")) 
{
	function validate_tag_count_page()
	{
		?>
		<h1>WordPress Validate Tag Count</h1>
		<p><?php _e('Введите необходимое количество <b>внутрених</b> ссылок на странице', 'validate_links'); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields('validate-tag-count-settings'); ?>
			<?php do_settings_sections('validate-tag-count-settings'); ?>
			<table class="form-table">
				<tr valign="top">
				<?php /*<th scope="row"><?php _e('Тэг', 'validate_links') ?>:</th>
					<td><input type="text" name="validate_tag_count__tag" placeholder="<?php _e('Введите название тэга', 'validate_links') ?>" value="<?php echo get_option('validate_tag_count__tag') ?>"/></td>
				</tr>*/ ?>
				<th scope="row"><?php _e('Количество', 'validate_links') ?>:</th>
					<td><input type="number" name="validate_tag_count__quantity" placeholder="<?php _e('Введите количество', 'validate_links') ?>" value="<?php echo get_option('validate_tag_count__quantity') ?>"/></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form><?php 
	}
}

