<?php

if ( ! defined( 'ABSPATH' ) ) { 
	exit; // restict for direct access
}
?>

<h1><?php echo esc_html__('WooCommerce Inquire Us & Disable Add To Cart Button', 'themelocationratc_hp'); ?></h1>

<div class="wrap">
	<form action="" method="post">
		<div class="hide_price_divs" style="padding-top: 0">

			<div id="hide_div">
				<div class="hide_div">
					<label><?php echo esc_html__('Hide for All Users', 'themelocationratc_hp'); ?></label>
					<div class="ratcwp_hide_field">
						<p style="color:red; margin: 0;"><a href="https://www.themelocation.com/remove-cart-button-plugin/" target="_blank" style="text-decoration: none; color: red;" class="asked-for-premium">Upgrade Premium Version</a></p>
					</div>
				</div>

				<div class="hide_div">
					<label><?php echo esc_html__('Hide for Guest Users', 'themelocationratc_hp'); ?></label>
					<input type="checkbox" name="ratcwp_enable_hide_pirce_guest" id="ratcwp_enable_hide_pirce_guest" value="yes" <?php echo checked('yes', esc_attr(get_option('ratcwp_enable_hide_pirce_guest'))); ?> />
				</div>

				<div class="hide_div">
					<label><?php echo esc_html__('Hide for Registered Users', 'themelocationratc_hp'); ?></label>
					<input type="checkbox" name="ratcwp_enable_hide_pirce_registered" id="ratcwp_enable_hide_pirce_registered" value="yes" <?php echo checked('yes', esc_attr(get_option('ratcwp_enable_hide_pirce_registered'))); ?> />
				</div>

				<div class="hide_div" id="userroles">
					<label><?php echo esc_html__('Select User Roles', 'themelocationratc_hp'); ?></label>
					<div class="ratcwp_hide_field">

						<select class="select_box wc-enhanced-select sel2" name="ratcwp_hide_user_role[]" id="ratcwp_hide_user_role"  multiple='multiple'>

							<?php
							$ratcwprole_hide_user_role = unserialize(get_option('ratcwp_hide_user_role'));
							
							global $wp_roles;
							$roles = $wp_roles->get_names();
							foreach ($roles as $key => $value) {
								?>
								<option value="<?php echo esc_attr($key); ?>"
									<?php
									if (!empty($ratcwprole_hide_user_role) && in_array($key, $ratcwprole_hide_user_role)) {
										echo 'selected';
									} ?>
								>
								<?php echo esc_attr($value); ?>		
								</option>
							<?php } ?>
						</select>
						<p><?php echo esc_html__('Select User Roles for which users you want to hide price and add to cart on frontend. If no user role is selected then price and add to cart will not be hidden for registered users.', 'themelocationratc_hp'); ?></p>
					</div>
					
				</div>

				<div class="hide_div">
					<label><?php echo esc_html__('Hide Price', 'themelocationratc_hp'); ?></label>
					<div class="ratcwp_hide_field">
						<p style="color:red; margin: 0;"><a href="https://www.themelocation.com/remove-cart-button-plugin/" target="_blank" style="text-decoration: none; color: red;" class="asked-for-premium">Upgrade Premium Version</a></p>
					</div>
				</div>

				<div class="hide_div" id="hp_price">
					<label><?php echo esc_html__('Price Text', 'themelocationratc_hp'); ?></label>
					<div class="ratcwp_hide_field">
						<input type="text" name="ratcwp_price_text" id="ratcwp_price_text" class="ratcwp_hp_input_field" />
						<p><?php echo esc_html__('This text will be shown in place of price in archive and product pages.', 'themelocationratc_hp'); ?></p>
					</div>
				</div>



				<div class="hide_div">
					<label><?php echo esc_html__('Add to Cart Button', 'themelocationratc_hp'); ?></label>
					<div class="ratcwp_hide_field">
						<select id="wpiudacb_disable_add_to_cart" name="ratcwp_hide_cart_button" class="select short" style="">
							<option value="default" <?php echo selected('default', esc_attr(get_option('ratcwp_hide_cart_button')), false); ?>>Default</option>
							<option value="remove_button" <?php echo selected('remove_button', esc_attr(get_option('ratcwp_hide_cart_button')), false); ?>>Remove Button</option>
							<option value="inquire_us" <?php echo selected('inquire_us', esc_attr(get_option('ratcwp_hide_cart_button')), false); ?>>Inquire Us</option>
						</select>
						<p><?php echo esc_html__('If this option is checked then Add To Cart button is hidden on the archive and product pages.', 'themelocationratc_hp'); ?> <div style="color: red"><a href="https://www.themelocation.com/remove-cart-button-plugin/" target="_blank" style="text-decoration: none; color: red;" class="asked-for-premium">Change Inquire us (text) by upgrading</a></div></p>
					</div>
				</div>

				<?php 
					if( get_option('ratcwp_hide_cart_button') == 'inquire_us' ) {
						$show_inquire = 'show_inquire';
					} else {
						$show_inquire = '';
					}
				?>

				<div class="hide_div hp_cart <?php echo esc_attr($show_inquire); ?>">
					<label><?php echo esc_html__('Inquire Us Text', 'themelocationratc_hp'); ?></label>
					<div class="ratcwp_hide_field">
						<p style="color:red; margin: 0;"><a href="https://www.themelocation.com/remove-cart-button-plugin/" target="_blank" style="text-decoration: none; color: red;" class="asked-for-premium">Upgrade Premium Version</a></p>
						<input type="text" name="ratcwp_cart_button_text" id="ratcwp_cart_button_text" class="ratcwp_hp_input_field" value="Inquire Us" style="display: none;" />
					</div>
				</div>

				<div class="hide_div hp_cart <?php echo esc_attr($show_inquire); ?>">
					<label><?php echo esc_html__('Inquire Us Button Link', 'themelocationratc_hp'); ?></label>
					<div class="ratcwp_hide_field">
						<input type="text" name="ratcwp_cart_button_link" id="ratcwp_cart_button_link" class="ratcwp_hp_input_field" value="<?php echo esc_attr(get_option('ratcwp_cart_button_link')); ?>" />
						<p><?php echo esc_html__('This link will replace Add to Cart Button link.', 'themelocationratc_hp'); ?></p>
					</div>
				</div>


				<div class="hide_div">
					<label><?php echo esc_html__('Select Products', 'themelocationratc_hp'); ?></label>
					<div class="ratcwp_hide_field">
						<select class="select_box wc-enhanced-select sel_pros" name="ratcwp_hide_products[]" id="ratcwp_hide_products"  multiple='multiple'>
							<?php
								$ratcwp_hide_products = unserialize(get_option('ratcwp_hide_products'));

							if (!empty($ratcwp_hide_products)) {

								foreach ( $ratcwp_hide_products as $pro) {

									$prod_post = get_post($pro);
									?>
									<option value="<?php echo intval($pro); ?>" selected="selected"><?php echo esc_attr($prod_post->post_title); ?></option>
									<?php 
								}
							}
							?>
						</select>
						<p><?php echo esc_html__('Select Products for which you want to hide price and add to cart.', 'themelocationratc_hp'); ?></p>
					</div>
				</div>


				<div class="hide_div">
					<label><?php echo esc_html__('Select Categories', 'themelocationratc_hp'); ?></label>
					<div class="ratcwp_hide_field">
						
						<div class="all_cats">
							<ul>
								<?php

								$pre_vals = unserialize(get_option('cps_hide_categories'));

								$args = array(
									'taxonomy' => 'product_cat',
									'hide_empty' => false,
									'parent'   => 0
								);

								$product_cat = get_terms( $args );
								foreach ($product_cat as $parent_product_cat) {
									?>
									<li class="par_cat">
										<input type="checkbox" class="parent" name="cps_hide_categories[]" id="cps_hide_categories" value="<?php echo intval($parent_product_cat->term_id); ?>" 
										<?php 
										if (!empty($pre_vals) && in_array($parent_product_cat->term_id, $pre_vals)) { 
											echo 'checked';
										}
										?>
										/>
										<?php echo esc_attr($parent_product_cat->name); ?>

										<?php
										$child_args         = array(
											'taxonomy' => 'product_cat',
											'hide_empty' => false,
											'parent'   => intval($parent_product_cat->term_id)
										);
										$child_product_cats = get_terms( $child_args );
										if (!empty($child_product_cats)) {
											?>
											<ul>
												<?php foreach ($child_product_cats as $child_product_cat) { ?>
													<li class="child_cat">
														<input type="checkbox" class="child parent" name="cps_hide_categories[]" id="cps_hide_categories" value="<?php echo intval($child_product_cat->term_id); ?>" 
														<?php
														if (!empty($pre_vals) &&in_array($child_product_cat->term_id, $pre_vals)) { 
															echo 'checked';
														}
														?>
														/>
														<?php echo esc_attr($child_product_cat->name); ?>

														<?php
														//2nd level
														$child_args2 = array(
															'taxonomy' => 'product_cat',
															'hide_empty' => false,
															'parent'   => intval($child_product_cat->term_id)
														);

														$child_product_cats2 = get_terms( $child_args2 );
														if (!empty($child_product_cats2)) {
															?>

															<ul>
																<?php foreach ($child_product_cats2 as $child_product_cat2) { ?>

																	<li class="child_cat">
																		<input type="checkbox" class="child parent" name="cps_hide_categories[]" id="cps_hide_categories" value="<?php echo intval($child_product_cat2->term_id); ?>" 
																		<?php
																		if (!empty($pre_vals) &&in_array($child_product_cat2->term_id, $pre_vals)) {
																			echo 'checked';
																		}
																		?>
																		/>
																		<?php echo esc_attr($child_product_cat2->name); ?>


																		<?php
																		//3rd level
																		$child_args3 = array(
																			'taxonomy' => 'product_cat',
																			'hide_empty' => false,
																			'parent'   => intval($child_product_cat2->term_id)
																		);

																		$child_product_cats3 = get_terms( $child_args3 );
																		if (!empty($child_product_cats3)) {
																			?>

																			<ul>
																				<?php foreach ($child_product_cats3 as $child_product_cat3) { ?>

																					<li class="child_cat">
																						<input type="checkbox" class="child parent" name="cps_hide_categories[]" id="cps_hide_categories" value="<?php echo intval($child_product_cat3->term_id); ?>" 
																						<?php
																						if (!empty($pre_vals) &&in_array($child_product_cat3->term_id, $pre_vals)) {
																							echo 'checked';
																						}
																						?>
																						/>
																						<?php echo esc_attr($child_product_cat3->name); ?>


																						<?php
																						//4th level
																						$child_args4 = array(
																							'taxonomy' => 'product_cat',
																							'hide_empty' => false,
																							'parent'   => intval($child_product_cat3->term_id)
																						);

																						$child_product_cats4 = get_terms( $child_args4 );
																						if (!empty($child_product_cats4)) {
																							?>

																							<ul>
																								<?php foreach ($child_product_cats4 as $child_product_cat4) { ?>

																									<li class="child_cat">
																										<input type="checkbox" class="child parent" name="cps_hide_categories[]" id="cps_hide_categories" value="<?php echo intval($child_product_cat4->term_id); ?>"
																										<?php
																										if (!empty($pre_vals) &&in_array($child_product_cat4->term_id, $pre_vals)) {
																											echo 'checked';
																										}
																										?>
																										/>
																										<?php echo esc_attr($child_product_cat4->name); ?>


																										<?php
																										//5th level
																										$child_args5 = array(
																											'taxonomy' => 'product_cat',
																											'hide_empty' => false,
																											'parent'   => intval($child_product_cat4->term_id)
																										);

																										$child_product_cats5 = get_terms( $child_args5 );
																										if (!empty($child_product_cats5)) {
																											?>

																											<ul>
																												<?php foreach ($child_product_cats5 as $child_product_cat5) { ?>

																													<li class="child_cat">
																														<input type="checkbox" class="child parent" name="cps_hide_categories[]" id="cps_hide_categories" value="<?php echo intval($child_product_cat5->term_id); ?>" 
																														<?php
																														if (!empty($pre_vals) &&in_array($child_product_cat5->term_id, $pre_vals)) {
																															echo 'checked';
																														}
																														?>
																														/>
																														<?php echo esc_attr($child_product_cat5->name); ?>


																														<?php
																														//6th level
																														$child_args6 = array(
																															'taxonomy' => 'product_cat',
																															'hide_empty' => false,
																															'parent'   => intval($child_product_cat5->term_id)
																														);

																														$child_product_cats6 = get_terms( $child_args6 );
																														if (!empty($child_product_cats6)) {
																															?>

																															<ul>
																																<?php foreach ($child_product_cats6 as $child_product_cat6) { ?>

																																	<li class="child_cat">
																																		<input type="checkbox" class="child" name="cps_hide_categories[]" id="cps_hide_categories" value="<?php echo intval($child_product_cat6->term_id); ?>" 
																																		<?php
																																		if (!empty($pre_vals) &&in_array($child_product_cat6->term_id, $pre_vals)) {
																																			echo 'checked';
																																		}
																																		?>
																																		/>
																																		<?php echo esc_attr($child_product_cat6->name); ?>
																																	</li>

																																<?php } ?>
																															</ul>

																														<?php } ?>

																													</li>

																												<?php } ?>
																											</ul>

																										<?php } ?>


																									</li>

																								<?php } ?>
																							</ul>

																						<?php } ?>


																					</li>

																				<?php } ?>
																			</ul>

																		<?php } ?>

																	</li>

																<?php } ?>
															</ul>

														<?php } ?>

													</li>
												<?php } ?>
											</ul>
										<?php } ?>

									</li>
									<?php
								}
								?>
							</ul>
						</div>
						<p><?php echo esc_html__('Select Categories for which Products you want to hide price and add to cart.', 'themelocationratc_hp'); ?></p>
						<p><?php echo esc_html__('Convert your Shop to showcase shop', 'themelocationratc_hp'); ?></p>
					</div>
				</div>

				<?php wp_nonce_field('ratcwprole_save_hide_price_nonce', 'ratcwprole_save_admin_options'); ?>
			
			</div>

			<p><?php submit_button(esc_html__('Save Settings', 'themelocationratc_hp' ), 'primary', 'ratcwprole_save_hide_price'); ?></p>
		</div>
	</form>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		document.querySelector('form').addEventListener('submit', function(e) {
			const guestCheckbox = document.getElementById('ratcwp_enable_hide_pirce_guest');
			const registeredCheckbox = document.getElementById('ratcwp_enable_hide_pirce_registered');
			
			if (!guestCheckbox.checked && !registeredCheckbox.checked) {
				e.preventDefault();
				alert('Please select at least one option: Hide for Guest Users or Hide for Registered Users');
				return false;
			}
		});
	});
</script>