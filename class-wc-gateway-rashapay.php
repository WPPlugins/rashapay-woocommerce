<?php
if (!defined('ABSPATH') ) exit;
function Load_RashaPay_Gateway() {
	
	if ( class_exists( 'WC_Payment_Gateway' ) && !class_exists( 'WC_Gateway_Rashapay' ) && !function_exists('Woocommerce_Add_RashaPay_Gateway') ) {
		
		add_filter('woocommerce_payment_gateways', 'Woocommerce_Add_RashaPay_Gateway' );
		function Woocommerce_Add_RashaPay_Gateway($methods) {
			$methods[] = 'WC_Gateway_Rashapay';
			return $methods;
		}
		
		class WC_Gateway_Rashapay extends WC_Payment_Gateway {
			
			public function __construct(){
				
				//by Woocommerce.ir
				$this->author = 'Woocommerce.ir';
				//by Woocommerce.ir
				
				
				$this->id = 'rashapay';
				$this->method_title = __('راشا پرداخت', 'woocommerce');
				$this->method_description = __( 'تنظیمات درگاه پرداخت راشا پرداخت برای افزونه فروشگاه ساز ووکامرس', 'woocommerce');
				$this->icon = apply_filters('WC_RashaPay_logo', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/logo.png');
				$this->has_fields = false;
				
				$this->init_form_fields();
				$this->init_settings();
				
				$this->title = $this->settings['title'];
				$this->description = $this->settings['description'];
				
				$this->merchant = $this->settings['merchant'];	
				
				$this->success_massage = $this->settings['success_massage'];
				$this->failed_massage = $this->settings['failed_massage'];
				
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) )
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				else
					add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );	
				add_action('woocommerce_receipt_'.$this->id.'', array($this, 'Send_to_RashaPay_Gateway_By_HANNANStd'));
				add_action('woocommerce_api_'.strtolower(get_class($this)).'', array($this, 'Return_from_RashaPay_Gateway_By_HANNANStd') );
				
			}

			public function admin_options(){
				$action = $this->author;
				do_action( 'WC_Gateway_Payment_Actions', $action );			
				parent::admin_options();
			}
		
			public function init_form_fields(){
				$this->form_fields = apply_filters('WC_RashaPay_Config', 
					array(
					
						'base_confing' => array(
							'title'       => __( 'تنظیمات پایه ای', 'woocommerce' ),
							'type'        => 'title',
							'description' => '',
						),
						'enabled' => array(
							'title'   => __( 'فعالسازی/غیرفعالسازی', 'woocommerce' ),
							'type'    => 'checkbox',
							'label'   => __( 'فعالسازی درگاه راشا پرداخت', 'woocommerce' ),						
							'description' => __( 'برای فعالسازی درگاه پرداخت راشا پرداخت باید چک باکس را تیک بزنید', 'woocommerce' ),
							'default' => 'yes',
							'desc_tip'    => true,
						),
						'title' => array(
							'title'       => __( 'عنوان درگاه', 'woocommerce' ),
							'type'        => 'text',
							'description' => __( 'عنوان درگاه که در طی خرید به مشتری نمایش داده میشود', 'woocommerce' ),
							'default'     => __( 'راشا پرداخت', 'woocommerce' ),
							'desc_tip'    => true,
						),
						'description' => array(
							'title'       => __( 'توضیحات درگاه', 'woocommerce' ),
							'type'        => 'text',
							'desc_tip'    => true,
							'description' => __( 'توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد', 'woocommerce' ),
							'default'     => __( 'پرداخت امن به وسیله کلیه کارت های عضو شتاب از طریق درگاه راشا پرداخت', 'woocommerce' )
						),
						'account_confing' => array(
							'title'       => __( 'تنظیمات حساب راشا پرداخت', 'woocommerce' ),
							'type'        => 'title',
							'description' => '',
						),
						'merchant' => array(
							'title'       => __( 'کد مشتری / کلید عمومی', 'woocommerce' ),
							'type'        => 'text',
							'description' => __( 'Consumer Key درگاه پرداخت راشا پرداخت', 'woocommerce' ),
							'default'     => '',
							'desc_tip'    => true
						),
						'payment_confing' => array(
							'title'       => __( 'تنظیمات عملیات پرداخت', 'woocommerce' ),
							'type'        => 'title',
							'description' => '',
						),
						'success_massage' => array(
							'title'       => __( 'پیام پرداخت موفق', 'woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {transaction_id} برای نمایش کد رهگیری راشا پرداخت استفاده نمایید .', 'woocommerce' ),
							'default'     => __( 'با تشکر از شما . سفارش شما با موفقیت پرداخت شد .', 'woocommerce' ),
						),
						'failed_massage' => array(
							'title'       => __( 'پیام پرداخت ناموفق', 'woocommerce' ),
							'type'        => 'textarea',
							'description' => __( 'متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید . این دلیل خطا از سایت راشا پرداخت ارسال میگردد .', 'woocommerce' ),
							'default'     => __( 'پرداخت شما ناموفق بوده است . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید .', 'woocommerce' ),
						),
					)
				);
			}

			public function process_payment( $order_id ) {
				$order = new WC_Order( $order_id );	
				return array(
					'result'   => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}

			public function Send_to_RashaPay_Gateway_By_HANNANStd($order_id){
				global $woocommerce;
				$woocommerce->session->order_id_rashapay = $order_id;
				$order = new WC_Order( $order_id );
				$currency = $order->get_order_currency();
				$currency = apply_filters( 'WC_RashaPay_Currency', $currency, $order_id );
				$action = $this->author;
				do_action( 'WC_Gateway_Payment_Actions', $action );			
				$form = '<form action="" method="POST" class="rashapay-checkout-form" id="rashapay-checkout-form">
						<input type="submit" name="rashapay_submit" class="button alt" id="rashapay-payment-button" value="'.__( 'پرداخت', 'woocommerce' ).'"/>
						<a class="button cancel" href="' . $woocommerce->cart->get_checkout_url() . '">' . __( 'بازگشت', 'woocommerce' ) . '</a>
					 </form><br/>';
				$form = apply_filters( 'WC_RashaPay_Form', $form, $order_id, $woocommerce );				
				
				do_action( 'WC_RashaPay_Gateway_Before_Form', $order_id, $woocommerce );	
				echo $form;
				do_action( 'WC_RashaPay_Gateway_After_Form', $order_id, $woocommerce );
					
				if ( isset($_POST["rashapay_submit"]) ) {
					$action = $this->author;
					do_action( 'WC_Gateway_Payment_Actions', $action );		
					
					if(!extension_loaded('curl')){
						$order->add_order_note( __( 'ماژول CURL روی هاست شما فعال نیست .', 'woocommerce') );
						wc_add_notice( __( 'ماژول CURL روی هاست فروشنده فعال نیست .', 'woocommerce') , 'error' );
						return false;
					}
									
					$Amount = intval($order->order_total);
					$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency );
					if ( strtolower($currency) == strtolower('IRT') || strtolower($currency) == strtolower('TOMAN')
						|| strtolower($currency) == strtolower('Iran TOMAN') || strtolower($currency) == strtolower('Iranian TOMAN')
						|| strtolower($currency) == strtolower('Iran-TOMAN') || strtolower($currency) == strtolower('Iranian-TOMAN')
						|| strtolower($currency) == strtolower('Iran_TOMAN') || strtolower($currency) == strtolower('Iranian_TOMAN')
						|| strtolower($currency) == strtolower('تومان') || strtolower($currency) == strtolower('تومان ایران')
					)
						$Amount = $Amount*10;
					else if ( strtolower($currency) == strtolower('IRHT') )							
						$Amount = $Amount*1000*10;
					else if ( strtolower($currency) == strtolower('IRHR') )							
						$Amount = $Amount*1000;
					
					$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_after_check_currency', $Amount, $currency );
					$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_irr', $Amount, $currency );
					$Amount = apply_filters( 'woocommerce_order_amount_total_RashaPay_gateway', $Amount, $currency );
			
					$MerchantID = $this->merchant;
					
					$Description = 'خرید به شماره سفارش : '.$order->get_order_number().' | خریدار : '.$order->billing_first_name.' '.$order->billing_last_name;
					$Email = $order->billing_email;
					$Email = $Email ? $Email : '';
					$Mobile = get_post_meta( $order_id, '_billing_phone', true ) ? get_post_meta( $order_id, '_billing_phone', true ) : '-';
					$Mobile = (is_numeric($Mobile) && $Mobile ) ? $Mobile : '-';
					$Paymenter = $order->billing_first_name.' '.$order->billing_last_name;
					$Paymenter = $Paymenter ? $Paymenter : '-';
					$ResNumber = intval($order->get_order_number());
					
					
					//Hooks for iranian developer
					$Description = apply_filters( 'WC_RashaPay_Description', $Description, $order_id );
					$Email = apply_filters( 'WC_RashaPay_Email', $Email, $order_id );
					$Mobile = apply_filters( 'WC_RashaPay_Mobile', $Mobile, $order_id );
					$Paymenter = apply_filters( 'WC_RashaPay_Paymenter', $Paymenter, $order_id );
					$ResNumber = apply_filters( 'WC_RashaPay_ResNumber', $ResNumber, $order_id );
					do_action( 'WC_RashaPay_Gateway_Payment', $order_id, $Description, $Email, $Mobile );
					
					
					$CallbackURL = add_query_arg( 'wc_order', $order_id , WC()->api_request_url('WC_Gateway_Rashapay') );
					
					$post = array(
						'consumer_key' => $MerchantID,
						'amount'       => $Amount,
						'email'        => $Email,
						'name'         => $Paymenter,
						'orderid'      => $order_id,
						'callback'     => $CallbackURL,
						'mobile'       => $Mobile,
						'description'  => $Description
					);
					
					$ch = curl_init('http://rashapay.com/srv/rest/rpaypaymentrequest');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_POST, 1);
					$resp = curl_exec($ch);
					curl_close($ch);
					$resp = json_decode($resp);

					if ($resp->status == 'response') {
				
						do_action( 'WC_RashaPay_Before_Send_to_Gateway', $order_id );
						
						echo '<form action="http://rashapay.com/srv/gatewaychannel/requestpayment/'.$resp->token.'" method="post" name="rashapay"><noscript><input type="submit" value="Pay" /></noscript></form><script>document.rashapay.submit();</script>';
						exit;
						
					}
					else {
						
						$fault = $resp->status;
						
						$Note = sprintf( __( 'خطا در هنگام ارسال به بانک : %s', 'woocommerce'), $this->Fault_RashaPay($fault) );
						$Note = apply_filters( 'WC_RashaPay_Send_to_Gateway_Failed_Note', $Note, $order_id, $fault );
						$order->add_order_note( $Note );
						
						
						$Notice = sprintf( __( 'در هنگام اتصال به بانک خطای زیر رخ داده است : <br/>%s', 'woocommerce'), $this->Fault_RashaPay($fault) );
						$Notice = apply_filters( 'WC_RashaPay_Send_to_Gateway_Failed_Notice', $Notice, $order_id, $fault );
						if ( $Notice )
							wc_add_notice( $Notice , 'error' );
						
						do_action( 'WC_RashaPay_Send_to_Gateway_Failed', $order_id, $fault );
					
					}
				}
			}

			public function Return_from_RashaPay_Gateway_By_HANNANStd(){
				
				global $woocommerce;
				$action = $this->author;
				do_action( 'WC_Gateway_Payment_Actions', $action );			
				if ( isset($_GET['wc_order']) ) 
					$order_id = $_GET['wc_order'];
				else
					$order_id = $woocommerce->session->order_id_rashapay;
				if ( $order_id ) {
				
					$order = new WC_Order($order_id);
					$currency = $order->get_order_currency();		
					$currency = apply_filters( 'WC_RashaPay_Currency', $currency, $order_id );
						
					if($order->status !='completed'){
						
						$MerchantID = $this->merchant;
						
						$post = array(
								'consumer_key' => $MerchantID,
								'orderid'      => $_POST['orderid'],
								'refid'        => $_POST['refid']
						);
						
						$ch = curl_init('http://rashapay.com/srv/rest/rpaypaymentverify');
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
						curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_POST, 1);
						$resp = curl_exec($ch);
						curl_close($ch);
						$resp = json_decode($resp);
						
						$status = 'failed';
								
						if($resp->status == 'response'){
							
							if ($resp->code == 0){
								$status = 'completed';
								$fault = 0;
							}
							else {
								$fault = $resp->code;
							}
						}
						else {
							$fault = $resp->status;
						}
						$transaction_id = isset($_POST['refid']) ? $_POST['refid'] : '-';
						
						if ( $status == 'completed') {
							$action = $this->author;
							do_action( 'WC_Gateway_Payment_Actions', $action );
							if ( $transaction_id && ( $transaction_id !=0 ) )
								update_post_meta( $order_id, '_transaction_id', $transaction_id );
														
							$order->payment_complete($transaction_id);
							$woocommerce->cart->empty_cart();
							
							
							$Note = sprintf( __('پرداخت موفقیت آمیز بود .<br/> کد رهگیری : %s', 'woocommerce' ), $transaction_id );
							$Note = apply_filters( 'WC_RashaPay_Return_from_Gateway_Success_Note', $Note, $order_id, $transaction_id );
							if ($Note)
								$order->add_order_note( $Note , 1 );
							
							$Notice = wpautop( wptexturize($this->success_massage));
							
							$Notice = str_replace("{transaction_id}",$transaction_id,$Notice);
							
							$Notice = apply_filters( 'WC_RashaPay_Return_from_Gateway_Success_Notice', $Notice, $order_id, $transaction_id );
							if ($Notice)
								wc_add_notice( $Notice , 'success' );
							
							
							do_action( 'WC_RashaPay_Return_from_Gateway_Success', $order_id, $transaction_id );
							
							wp_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );
							exit;
						}
						else {
							
							$action = $this->author;
							do_action( 'WC_Gateway_Payment_Actions', $action );
							
							$tr_id = ( $transaction_id && $transaction_id != 0 ) ? ('<br/>کد پیگیری : '.$transaction_id) : '';
							
							$Note = sprintf( __( 'خطا در هنگام بازگشت از بانک : %s %s', 'woocommerce'), $this->Fault_RashaPay($fault) , $tr_id );
							
							$Note = apply_filters( 'WC_RashaPay_Return_from_Gateway_Failed_Note', $Note, $order_id, $transaction_id, $fault );
							if ($Note)
								$order->add_order_note( $Note , 1 );
							
							
							$Notice = wpautop( wptexturize($this->failed_massage));
							
							$Notice = str_replace("{transaction_id}",$transaction_id,$Notice);
							
							$Notice = str_replace("{fault}",$this->Fault_RashaPay($fault),$Notice);
							$Notice = apply_filters( 'WC_RashaPay_Return_from_Gateway_Failed_Notice', $Notice, $order_id, $transaction_id, $fault );
							if ($Notice)
								wc_add_notice( $Notice , 'error' );
							
							do_action( 'WC_RashaPay_Return_from_Gateway_Failed', $order_id, $transaction_id, $fault );
							
							wp_redirect(  $woocommerce->cart->get_checkout_url()  );
							exit;
						}
				
				
					}
					else {
						$action = $this->author;
						do_action( 'WC_Gateway_Payment_Actions', $action );	
						$transaction_id = get_post_meta( $order_id, '_transaction_id', true );
						
						$Notice = wpautop( wptexturize($this->success_massage));
						
						$Notice = str_replace("{transaction_id}",$transaction_id,$Notice);
						
						$Notice = apply_filters( 'WC_RashaPay_Return_from_Gateway_ReSuccess_Notice', $Notice, $order_id, $transaction_id );
						if ($Notice)
							wc_add_notice( $Notice , 'success' );
						
						
						do_action( 'WC_RashaPay_Return_from_Gateway_ReSuccess', $order_id, $transaction_id );
							
						wp_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );
						exit;
					}
				}
				else {
					
					$action = $this->author;
					do_action( 'WC_Gateway_Payment_Actions', $action );		
					$fault = __('شماره سفارش وجود ندارد .', 'woocommerce' );
					$Notice = wpautop( wptexturize($this->failed_massage));
					$Notice = str_replace("{fault}",$fault, $Notice);
					$Notice = apply_filters( 'WC_RashaPay_Return_from_Gateway_No_Order_ID_Notice', $Notice, $order_id, $fault );
					if ($Notice)
						wc_add_notice( $Notice , 'error' );		
					
					do_action( 'WC_RashaPay_Return_from_Gateway_No_Order_ID', $order_id, $transaction_id, $fault );
						
					wp_redirect(  $woocommerce->cart->get_checkout_url()  );
					exit;
				}
			}

			private static function Fault_RashaPay($err_code){
				
				$message = __('در حین پرداخت خطای سیستمی رخ داده است .', 'woocommerce' );
				
				switch($err_code){
					
					case '11' :
						$message = __( 'خطا در پرداخت رخ داده است.', 'woocommerce' );
					break;
					
					case '11' :
						$message = __( 'rasha_ckay تعریف نشده است.', 'woocommerce');
					break;

					case '12':
						$message = __( 'خطا در بارگزاری اطلاعات سایت.', 'woocommerce');
					break;

					case '13':
						$message = __( 'آی پی درخواست کننده غیر مجازاست.', 'woocommerce');
					break;
					
					case '14':
						$message = __( 'این شماره سفارش قبلاً ارسال شده است.', 'woocommerce');
					break;
							
					case '15':
						$message = __( 'مبلغ باید به عدد باشد.', 'woocommerce');
					break;
						
					case '16':
						$message = __( 'فرمت ایمیل صحیح نیست.', 'woocommerce');
					break;
						
					case '17':
						$message = __( 'صفحه برگشت تعریف نشده است.', 'woocommerce');
					break;
						
					case '18':
						$message = __( 'تراکنشی در سیستم ثبت نشده است.', 'woocommerce');
					break;
					
			
				}
				
				return $message;
			}
 
		}
	}
}
add_action('plugins_loaded', 'Load_RashaPay_Gateway', 0);