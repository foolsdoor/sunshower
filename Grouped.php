<?php
/**
 * Grouped product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/grouped.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.8.0
 */

defined( 'ABSPATH' ) || exit;

global $product, $post;
do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<?php 
	/**
	 * Filtro la variabile globale $grouped_products per togliere i prodotti non pubblicati;
	 */
	$grouped_products = array_filter($grouped_products, function($p) {
		return $p->get_status() === 'publish';
	});

	// Riordino l'array in base al parametro 'menu_order';
	usort($grouped_products, function($a, $b) {
		return $a->get_menu_order() - $b->get_menu_order();
	});
?>

<?php
$_warranty = wc_get_product_terms( $product->get_id(), 'pa_warranty', array( 'fields' => 'names' ) );
$warranty = array_shift( $_warranty );
?>

<div id="single-product-add-to-cart-accordion" class="accordion-container hidden-accordion">
	<?php $c = 0; ?>
	<?php foreach ( $grouped_products as $grouped_product_child ) { ?>
		<?php $c++; ?>
		<?php /*** Custom fields; */ ?>
		<?php $pid = $grouped_product_child->get_id(); ?>
		<?php $msrp = get_post_meta($pid, '_msrp', true) ?? 0; ?>
		<?php $sale_price = get_post_meta( $pid, '_sale_price', true); ?>
		<?php $regular_price = get_post_meta( $pid, '_regular_price', true); ?>
		<?php $price = $sale_price ? $sale_price : $regular_price; ?>
		<?php $mpn = get_post_meta($pid, '_wpmr_mpn', true); ?>
		<?php $backorder_text = get_post_meta($pid, '_backorder_text', true); ?>
		<?php $backorder_date = get_post_meta($pid, '_backorder_date', true); ?>
		<?php $inthepast = false;
			if ( $backorder_date ) {
				$date = date("d-m-Y", strtotime($backorder_date));
				$now = date("d-m-Y");
				$inthepast = $date < $now;
			}
		?>
		<?php $taxedprice = wc_format_decimal(wc_get_price_including_tax($grouped_product_child, array('price' => $price )), 2); ?>
		<?php $taxedmsrp = wc_format_decimal(wc_get_price_including_tax($grouped_product_child, array('price' => $msrp )), 2); ?>
        <?php
        // Check if current user has a wholesale role
        $user = wp_get_current_user();
        $isWholesaleUser = in_array('wholesale_customer', $user->roles);
        // If user is wholesale user, adjust the price if wholesale price exists
        if ($isWholesaleUser) {
            $_wholesale_price = get_post_meta($pid, 'wholesale_customer_wholesale_price', true);
            if ($_wholesale_price) {
                $taxedprice = wc_format_decimal(wc_get_price_including_tax($grouped_product_child, ['price' => $_wholesale_price]), 2);
            }
        }
        ?>
		<div class="ac" data-id="<?= $pid; ?>" >
			<div class="jet-toggle__control elementor-menu-anchor ac-header" tabindex="0">
				<div class="jet-toggle__label-text ac-trigger">
					<table>
						<tr>
							<td width="90%">
                                <?php if ( $sale_price ) : ?>
                                    <span class="ac-header__product-description__special-price">SPECIAL</span>
                                <?php endif; ?>
								<span class="ac-header__product-description">
									<?= $grouped_product_child->get_name(); ?>
								</span>
								<small class="ac-header__sku">SKU: <?= $grouped_product_child->get_sku(); ?></small>
							</td>
							<td width="10%">
								<span class="ac-header__price">
									<?php echo '$' . $taxedprice . '<br>'; ?>
									<?php if ( $msrp && $price < $msrp ) echo '<span class="price-line-through" style="text-decoration: line-through;">$' . number_format( $taxedmsrp, 2, '.', '' ) . '</span>'; ?>
								</span>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<div class="jet-toggle__content ac-panel">
				<div class="jet-toggle__content-inner">
					<?php if ( $grouped_product_child->is_type( 'variable' ) ) :
						wp_enqueue_script( 'wc-add-to-cart-variation' );
						$attributes = $grouped_product_child->get_variation_attributes();
						$attribute_keys = array_keys( $attributes );
						$available_variations = $grouped_product_child->get_available_variations();
						$variations_json = wp_json_encode( $available_variations );
						$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
						$html  = '<div class="label">';
        
                        if ( $sale_price ) {
                            //$html .= '<p class="jet-toggle__content-inner__in-stock-label red">SPECIAL PRICE</p>';
                        }
        
						$html .= '<h4>' . $grouped_product_child->get_name() . '</h4>';
                        
                        $html .= '<br><strong>SKU</strong>&nbsp;' . $grouped_product_child->get_sku();

						if ( $mpn ) $html .= '<br><strong>MPN</strong>&nbsp;' . $mpn;

						if ( $warranty ) $html .= '<br><strong>Warranty</strong>&nbsp;' . $warranty;

						if ( $grouped_product_child->get_stock_quantity() > 0 || $inthepast ) {
							$html .= '<p class="jet-toggle__content-inner__in-stock-label">In stock</p>';
						} else {
							if ( $backorder_text && !$backorder_date ) $html .= '<br><strong>' . $backorder_text . '</strong>';
							if ( $backorder_date ) $html .= '<br>Available from <strong>' . date("d-m-Y", strtotime($backorder_date)) . '</strong>';
						}
						$html .= '</div>';
						echo $html;
					?>
						<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $grouped_product_child->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $pid ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
							<?php do_action( 'woocommerce_before_variations_form' ); ?>
							<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
								<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
							<?php else : ?>
								<table class="variations" cellspacing="0">
									<tbody>
										<?php foreach ( $attributes as $attribute_name => $options ) : ?>
											<?php $attribute_complete_name = 'attribute_' . esc_attr( sanitize_title( $attribute_name ) ); ?>
											<tr>
												<td class="label">
													<label for="<?= $attribute_complete_name; ?>"><?php echo wc_attribute_label( $attribute_name ); // WPCS: XSS ok. ?></label>
												</td>
												<td class="value">
													<select @change="onVariationChange" data-select-id="<?= $attribute_complete_name . '-' . $pid; ?>" v-model="variation_model['<?= $attribute_complete_name; ?>']" name="<?= $attribute_complete_name; ?>" data-attribute_name="<?= $attribute_complete_name; ?>">
														<!-- <option value="">Choose an option</option>	 -->
														<?php foreach ( $options as $option ) : ?>
															<?php $label = attribute_slug_to_title($attribute_name, $option); ?>
															<option value="<?= $option; ?>" class="attached enabled">
																<?= $label; ?>
															</option>
														<?php endforeach; ?>
													</select>
												</td>
											</tr>
										<?php endforeach; ?>
										<button @click="resetVariations" class="reset_variations">RESET</button>
									</tbody>
								</table>

								<div class="single_variation_wrap">
									<?php
										/**
										 * Hook: woocommerce_before_single_variation.
										 */
										do_action( 'woocommerce_before_single_variation' );

										/**
										 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
										 *
										 * @since 2.4.0
										 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
										 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
										 */
										do_action( 'woocommerce_single_variation' );

										/**
										 * Hook: woocommerce_after_single_variation.
										 */
										do_action( 'woocommerce_after_single_variation' );
									?>
								</div>
							<?php endif; ?>

							<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $pid ); ?>" />
							<?php do_action( 'woocommerce_after_variations_form' ); ?>
						</form>

					<?php else : ?>
						<?php $image_id = $grouped_product_child->get_image_id(); ?>
						<?php
                            $image_src = wp_get_attachment_image_src( $image_id, 'woocommerce_single' );
                            $image = is_array($image_src) ? $image_src[0] : 'https://sunshoweronline.com.au/wp-content/uploads2022/10/woocommerce-image-placeholder-1.jpg';
        
                        ?>
						<form data-product-image="<?= $image; ?>" class="grouped_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
							<!-- <table cellspacing="0" class="woocommerce-grouped-product-list group_table"> -->
								<!-- <tbody> -->
									<?php
									$quantites_required      = false;
									$previous_post           = $post;
									$grouped_product_columns = apply_filters(
										'woocommerce_grouped_product_columns',
										array(
											'label',
											'price',
											'quantity',
										),
										$product
									);
									$show_add_to_cart_button = false;

									do_action( 'woocommerce_grouped_product_list_before', $grouped_product_columns, $quantites_required, $product );

									$post_object        = get_post( $pid );
									$quantites_required = $quantites_required || ( $grouped_product_child->is_purchasable() && ! $grouped_product_child->has_options() );
									$post               = $post_object; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
									setup_postdata( $post );

									if ( $grouped_product_child->is_in_stock() ) {
										$show_add_to_cart_button = true;
									}

									$oos = ! $show_add_to_cart_button ? 'out-of-stock' : '';
        
                                    // echo '<small><b>';
                                    // echo 'purchasable: ' . $grouped_product_child->is_purchasable() . '<br>';
                                    // echo 'has_options: ' . $grouped_product_child->has_options() . '<br>';
                                    // echo 'is_in_stock: ' . $grouped_product_child->is_in_stock() . '<br>';
                                    // echo 'is_on_backorder: ' . $grouped_product_child->is_on_backorder() . '<br>';
                                    // echo '</b></small>';

                                    // echo '<tr id="product-' . esc_attr( $pid ) . '" class="woocommerce-grouped-product-list-item ' . $oos . ' ' . esc_attr( implode( ' ', wc_get_product_class( '', $grouped_product_child ) ) ) . '">';
									echo '<div class="product-grid" id="product-' . esc_attr( $pid ) . '" class="woocommerce-grouped-product-list-item ' . $oos . ' ' . esc_attr( implode( ' ', wc_get_product_class( '', $grouped_product_child ) ) ) . '">';

									// Output columns for each product.
									foreach ( $grouped_product_columns as $column_id ) {
										do_action( 'woocommerce_grouped_product_list_before_' . $column_id, $grouped_product_child );

										switch ( $column_id ) {
											case 'quantity':
												ob_start();
												if (
													!$grouped_product_child->is_purchasable() ||
													$grouped_product_child->has_options() ||
													!$grouped_product_child->is_in_stock()
												) {
													// woocommerce_template_loop_add_to_cart();
												} elseif ( $grouped_product_child->is_sold_individually() ) {
													echo '<input type="checkbox" name="' . esc_attr( 'quantity[' . $pid . ']' ) . '" value="1" class="wc-grouped-product-add-to-cart-checkbox" />';
												} else {
													do_action( 'woocommerce_before_add_to_cart_quantity' );

													woocommerce_quantity_input(
														array(
															'input_name'  => 'quantity',
															'input_value' => 1, // phpcs:ignore WordPress.Security.NonceVerification.Missing
															// 'input_value' => isset( $_POST['quantity'][ $pid ] ) ? wc_stock_amount( wc_clean( wp_unslash( $_POST['quantity'][ $pid ] ) ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
															'min_value'   => apply_filters( 'woocommerce_quantity_input_min', 1, $grouped_product_child ),
															'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $grouped_product_child->get_max_purchase_quantity(), $grouped_product_child ),
															'placeholder' => '1',
														)
													);

													do_action( 'woocommerce_after_add_to_cart_quantity' );
												}

												$value = ob_get_clean();
												break;
											case 'label':
												$value  = '<div class="label">';
                                                if ( $sale_price ) {
                                                    //$value .= '<p class="jet-toggle__content-inner__in-stock-label red">SPECIAL PRICE</p>';
                                                }
												$value .= '<h4>' . $grouped_product_child->get_name() . '</h4>';
                                                $value .= '<br><strong>SKU</strong>&nbsp;' . $grouped_product_child->get_sku();
												if ( $mpn ) $value .= '<br><strong>MPN</strong>&nbsp;' . $mpn;
												if ( $warranty ) $value .= '<br><strong>Warranty</strong>&nbsp;' . $warranty;

												if ( $grouped_product_child->get_stock_quantity() > 0 || $inthepast ) {
													$value .= '<p class="jet-toggle__content-inner__in-stock-label">In stock</p>';
												} else {
													if ( $backorder_text && !$backorder_date ) $value .= '<p class="jet-toggle__content-inner__backorder-label"><strong>' . $backorder_text . '</strong></p>';
													if ( $backorder_date ) $value .= '<p class="jet-toggle__content-inner__out-of-stock-label">Available from <strong>' . date('j F Y', strtotime($backorder_date)) . '</strong></p>';
												}

												// if ( $backorder_text && !$backorder_data ) $value .= '<br><strong>' . $backorder_text . '</strong>';
												// if ( $backorder_data ) $value .= '<br><strong>' . $backorder_data . '</strong>';
												// $value .= $grouped_product_child->is_visible() ? '<a href="' . esc_url( apply_filters( 'woocommerce_grouped_product_list_link', $grouped_product_child->get_permalink(), $pid ) ) . '">' . $grouped_product_child->get_name() . '</a>' : $grouped_product_child->get_name();
												$value .= '</div>';
												break;
											case 'price':
												ob_start();
												get_template_part('woocommerce/custom-templates/sale-drop', null, $grouped_product_child);
												$value = ob_get_clean();
												break;
											default:
												$value = '';
												break;
										}

										echo '<div class="woocommerce-grouped-product-list-item__' . esc_attr( $column_id ) . '">' . apply_filters( 'woocommerce_grouped_product_list_column_' . $column_id, $value, $grouped_product_child ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

										do_action( 'woocommerce_grouped_product_list_after_' . $column_id, $grouped_product_child );
									}

									if ( $show_add_to_cart_button ) echo '<div class="woocommerce-grouped-product-list-item__button"><button type="submit" class="single_add_to_cart_button button alt">ADD TO CART</button></div>';

									echo '</div>';
									$post = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
									setup_postdata( $post );

									do_action( 'woocommerce_grouped_product_list_after', $grouped_product_columns, $quantites_required, $product );
									?>
								<!-- </tbody> -->
							<!-- </table> -->
							<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $pid ); ?>" />
							<input type="hidden" name="product_id" value="<?php echo esc_attr( $pid ); ?>" />
							<input type="hidden" name="variation_id" value="" />
						</form>
					<?php endif; ?>
					<?php if ( current_user_can('administrator') ) {
						echo '<a target="_blank" href="' . get_edit_post_link($pid) . '"><small>Edit product</small></a>';
					} ?>
				</div>
			</div>
		</div>
	<?php } ?>
</div>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
