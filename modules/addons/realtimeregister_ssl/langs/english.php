<?php

$_LANG['token']        = ', Error Token:';
$_LANG['generalError'] = 'Something has went wrong. Please check the logs and contact an administrator';

// Realtime Register Ssl configuration
$_LANG['addonAA']['pagesLabels']['label']['apiConfiguration']                                          = 'Configuration';
$_LANG['addonAA']['apiConfiguration']['crons']['header']                                               = 'Crons';
//synchronization cron
$_LANG['addonAA']['apiConfiguration']['DailyCron']['pleaseNote']                             = 'Please Note:';
$_LANG['addonAA']['apiConfiguration']['DailyCron']['info']                                   = 'In order to enable automatic daily synchronization, please set up a following cron command line:';
$_LANG['addonAA']['apiConfiguration']['DailyCron']['commandLine']['cronFrequency']           = '0 0 * * *';
//processing cron
$_LANG['addonAA']['apiConfiguration']['cronProcessing']['pleaseNote']                             = 'Please Note:';
$_LANG['addonAA']['apiConfiguration']['cronProcessing']['info']                                   = 'In order to enable automatic synchronization of processing orders every 5th minutes, please set up a following cron command line:';
$_LANG['addonAA']['apiConfiguration']['cronProcessing']['commandLine']['cronFrequency']           = '*/5 * * * *';

//synchronization cron
$_LANG['addonAA']['apiConfiguration']['cronSynchronization']['pleaseNote']                             = 'Please Note:';
$_LANG['addonAA']['apiConfiguration']['cronSynchronization']['info']                                   = 'In order to enable automatic synchronization, please set up a following cron command line (every hour suggested):';
$_LANG['addonAA']['apiConfiguration']['cronSynchronization']['commandLine']['cronFrequency']           = '0 */1 * * *';
//summary order cron
$_LANG['addonAA']['apiConfiguration']['cronSSLSummaryStats']['pleaseNote']                             = 'Please Note:';
$_LANG['addonAA']['apiConfiguration']['cronSSLSummaryStats']['info']                                   = 'In order to enable load current SSL orders status, please set up a following cron command line (every 4 hours suggested):';
$_LANG['addonAA']['apiConfiguration']['cronSSLSummaryStats']['commandLine']['cronFrequency']           = '1 */4 * * *';
//customers notification and creating renewals
$_LANG['addonAA']['apiConfiguration']['cronRenewal']['pleaseNote']                                     = 'Please Note:';
$_LANG['addonAA']['apiConfiguration']['cronRenewal']['info']                                           = 'In order to send customers notifications of expiring services and create renewal invoices for services that expire within the selected number of days, set the following command line cron (once a day suggested):';
$_LANG['addonAA']['apiConfiguration']['cronRenewal']['commandLine']['cronFrequency']                   = '0 0 * * *';
//customers send certificate
$_LANG['addonAA']['apiConfiguration']['cronSendCertificate']['pleaseNote']                             = 'Please Note:';
$_LANG['addonAA']['apiConfiguration']['cronSendCertificate']['info']                                   = 'In order to send a certificate to the client when the SSL order changes to active status, set the following command line cron (every 3 hours suggested):';
$_LANG['addonAA']['apiConfiguration']['cronSendCertificate']['commandLine']['cronFrequency']           = '0 */3 * * *';
//customers send certificate
$_LANG['addonAA']['apiConfiguration']['cronPriceUpdater']['pleaseNote']                             = 'Please Note:';
$_LANG['addonAA']['apiConfiguration']['cronPriceUpdater']['info']                                   = 'In order to synchronize the WHMCS product prices with the API product prices, set the following command line cron (every 3rd day suggested):';
$_LANG['addonAA']['apiConfiguration']['cronPriceUpdater']['commandLine']['cronFrequency']           = '0 0 */3 * *';
//customers send certificate
$_LANG['addonAA']['apiConfiguration']['cronCertificateDetailsUpdater']['pleaseNote']                             = 'Please Note:';
$_LANG['addonAA']['apiConfiguration']['cronCertificateDetailsUpdater']['info']                                   = 'In order to synchronize certificate details in WHMCS with the certificate details in API, set the following command line cron (once a day suggested):';
$_LANG['addonAA']['apiConfiguration']['cronCertificateDetailsUpdater']['commandLine']['cronFrequency']           = '0 0 * * *';
//
$_LANG['addonAA']['apiConfiguration']['item']['header']                                                = 'API Configuration';
$_LANG['addonAA']['apiConfiguration']['item']['api_login']['label']                                    = 'API Key';
$_LANG['addonAA']['apiConfiguration']['item']['api_password']['label']                                 = 'Password';
$_LANG['addonAA']['apiConfiguration']['item']['tech_legend']['label']                                  = 'Technical Contact';
$_LANG['addonAA']['apiConfiguration']['item']['csr_generator_legend']['label']                         = 'CSR Generator';
$_LANG['addonAA']['apiConfiguration']['item']['display_csr_generator']['label']                        = 'Allow To Use CSR Generator';
$_LANG['addonAA']['apiConfiguration']['item']['profile_data_csr']['label']                             = 'Use Profile Data for CSR';
$_LANG['addonAA']['apiConfiguration']['item']['auto_install_panel']['']['autoInstallPanel']          = 'Tick this box if you want to use automatic certificate installation';
$_LANG['addonAA']['apiConfiguration']['item']['default_csr_generator_country']['description']          = 'The default selection';
$_LANG['addonAA']['apiConfiguration']['item']['display_ca_summary']['label']                           = 'Display Orders Summary';
$_LANG['addonAA']['apiConfiguration']['item']['client_area_summary_orders']['label']                   = 'Client Area Orders Summary';
$_LANG['addonAA']['apiConfiguration']['item']['validation_settings']['label']                          = 'Validation Settings';
$_LANG['addonAA']['apiConfiguration']['item']['disable_email_validation']['label']                     = 'Disable Email Validation';
$_LANG['addonAA']['apiConfiguration']['item']['summary_expires_soon_days']['label']                    = 'Expires Soon';
$_LANG['addonAA']['apiConfiguration']['item']['summary_expires_soon_days']['description']              = 'Count SSL order in statistics if it expires in fewer or equal days than the above selection.';
$_LANG['addonAA']['apiConfiguration']['item']['send_certificate_template']['label']                    = 'Send Certificate Email Template';
$_LANG['addonAA']['apiConfiguration']['item']['send_certificate_template']['description']              = 'To send an SSL certificate through the chosen template, edit it and place the {$ssl_certificate} field in it.';
//
$_LANG['addonAA']['apiConfiguration']['item']['price_rate']['label']                                   = 'Rate for currency from RealtimeRegisterSSL';
$_LANG['addonAA']['apiConfiguration']['item']['rate']['label']                                         = 'Rate';
$_LANG['addonAA']['apiConfiguration']['item']['data_migration_legend']['label']                        = 'Data & Configuration Migration';
$_LANG['addonAA']['apiConfiguration']['item']['data_migration']['content']                             = 'Migrate';
$_LANG['addonAA']['apiConfiguration']['modal']['import']                                               = 'Migrate';
$_LANG['addonAA']['apiConfiguration']['modal']['close']                                                = 'Close';
$_LANG['addonAA']['apiConfiguration']['modal']['migrationData']                                        = 'Import Data & Configuration';
$_LANG['addonAA']['apiConfiguration']['migrationOldModuleDataExixts']                                  = 'There are products or services associated with the Realtime Register SSL WHMCS module:';
$_LANG['addonAA']['apiConfiguration']['migrationProductIDs']                                           = 'Product IDs: ';
$_LANG['addonAA']['apiConfiguration']['migrationServiceIDs']                                           = 'Service IDs: ';
$_LANG['addonAA']['apiConfiguration']['migrationPerformMigration']                                     = 'Perform data migration to associate configuration and data with the RealtimeRegisterSSL WHMCS module.';
$_LANG['addonAA']['apiConfiguration']['modal']['dataMigrationInfo']                                    = 'You are about to migrate data and configuration from Realtime Register SSL WHMCS module, this procedure is irreversible.';
$_LANG['addonAA']['apiConfiguration']['modal']['dataMigrationInfo2']                                   = 'Activities that will be performed:';
$_LANG['addonAA']['apiConfiguration']['modal']['dataMigrationInfoAction'][0]                           = 'import of the addon configuration';
$_LANG['addonAA']['apiConfiguration']['modal']['dataMigrationInfoAction'][1]                           = 'update of existing products (change of assigned module)';
$_LANG['addonAA']['apiConfiguration']['modal']['dataMigrationInfoAction'][2]                           = 'update of existing services (change of assigned module)';
$_LANG['addonAA']['apiConfiguration']['messages']['data_migration_success']                            = 'Data and configuration have been imported successfully. The page will automatically reloaded after 5 seconds.';
//
$_LANG['addonAA']['apiConfiguration']['item']['renewal_settings_legend']['label']                      = 'Renewal Settings';
$_LANG['addonAA']['apiConfiguration']['item']['logs_settings_legend']['label']                      = 'Logs Settings';
$_LANG['addonAA']['apiConfiguration']['item']['auto_renew_invoice_reccuring']['label']                 = 'Recuring Orders';
$_LANG['addonAA']['apiConfiguration']['item']['auto_renew_invoice_reccuring']['description']           = 'Create automatical renewal invoice';
$_LANG['addonAA']['apiConfiguration']['item']['send_expiration_notification_reccuring']['label']       = '';
$_LANG['addonAA']['apiConfiguration']['item']['renew_invoice_days_reccuring']['description']           = 'Days before expiry';
$_LANG['addonAA']['apiConfiguration']['item']['send_expiration_notification_reccuring']['description'] = 'Send expiration notifications';
$_LANG['addonAA']['apiConfiguration']['item']['auto_renew_invoice_one_time']['label']                  = 'One Time Orders';
$_LANG['addonAA']['apiConfiguration']['item']['auto_renew_invoice_one_time']['description']            = 'Create automatical renewal invoice';
$_LANG['addonAA']['apiConfiguration']['item']['send_expiration_notification_one_time']['label']        = '';
$_LANG['addonAA']['apiConfiguration']['item']['renew_invoice_days_one_time']['description']            = 'Days before expiry';
$_LANG['addonAA']['apiConfiguration']['item']['send_expiration_notification_one_time']['description']  = 'Send expiration notifications';


$_LANG['addonAA']['apiConfiguration']['item']['automatic_processing_of_renewal_orders']['label']                  = '';
$_LANG['addonAA']['apiConfiguration']['item']['automatic_processing_of_renewal_orders']['description']            = 'Automatic processing of renewal orders';
$_LANG['addonAA']['apiConfiguration']['item']['renewal_invoice_status_unpaid']['label']                  = '';
$_LANG['addonAA']['apiConfiguration']['item']['renewal_invoice_status_unpaid']['description']            = 'Set the status of Unpaid for renewal invoices (default is payment pending)';
$_LANG['addonAA']['apiConfiguration']['item']['sidebar_templates']['label']                  = 'List of pages with visible sidebar';
$_LANG['addonAA']['apiConfiguration']['item']['sidebar_templates']['description']            = 'Enter a list of pages separated by a comma. If you leave the field sidebar empty, it will be visible on each page. (Example: clientareaproducts,clientareaproductdetails,clientareainvoices)';
$_LANG['addonAA']['apiConfiguration']['item']['custom_guide']['label']                  = 'Custom Guide on Product Page';
$_LANG['addonAA']['apiConfiguration']['item']['custom_guide']['description']            = '';
$_LANG['addonAA']['apiConfiguration']['item']['renew_new_order']['label']                  = '';
$_LANG['addonAA']['apiConfiguration']['item']['renew_new_order']['description']            = 'Renew order via existing order';
$_LANG['addonAA']['apiConfiguration']['item']['visible_renew_button']['label']                  = '';
$_LANG['addonAA']['apiConfiguration']['item']['visible_renew_button']['description']            = 'Visible "Renew" button in Client Area';
$_LANG['addonAA']['apiConfiguration']['item']['save_activity_logs']['label']                  = 'Activity log';
$_LANG['addonAA']['apiConfiguration']['item']['save_activity_logs']['description']            = 'Tick this field to save logs';

$_LANG['addonAA']['apiConfiguration']['item']['api_test']['description'] = 'Enable for test environment';
$_LANG['addonAA']['apiConfiguration']['item']['api_test']['label'] = '';
//
$_LANG['addonAA']['apiConfiguration']['item']['tech_firstname']['label']                               = 'First Name';
$_LANG['addonAA']['apiConfiguration']['item']['use_admin_contact']['label']                            = 'Use Administrative Contact Details';
$_LANG['addonAA']['apiConfiguration']['item']['tech_lastname']['label']                                = 'Last Name';
$_LANG['addonAA']['apiConfiguration']['item']['tech_organization']['label']                            = 'Organization Name';
$_LANG['addonAA']['apiConfiguration']['item']['tech_title']['label']                                   = 'Job Title';
$_LANG['addonAA']['apiConfiguration']['item']['tech_addressline1']['label']                            = 'Address';
$_LANG['addonAA']['apiConfiguration']['item']['tech_phone']['label']                                   = 'Phone Number';
$_LANG['addonAA']['apiConfiguration']['item']['tech_email']['label']                                   = 'Email Address';
$_LANG['addonAA']['apiConfiguration']['item']['tech_city']['label']                                    = 'City';
$_LANG['addonAA']['apiConfiguration']['item']['tech_country']['label']                                 = 'Country';
$_LANG['addonAA']['apiConfiguration']['item']['tech_fax']['label']                                     = 'Fax Number';
$_LANG['addonAA']['apiConfiguration']['item']['tech_postalcode']['label']                              = 'Zip Code';
$_LANG['addonAA']['apiConfiguration']['item']['tech_region']['label']                                  = 'State/Region';

$_LANG['addonAA']['apiConfiguration']['item']['testConnection']['content'] = 'Test Connection';
$_LANG['addonAA']['apiConfiguration']['item']['saveItem']['label']         = 'Save';
$_LANG['addonAA']['pagesLabels']['label']['productsConfiguration']         = 'Products Configuration';
$_LANG['addonAA']['pagesLabels']['label']['productsCreator']               = 'Product Creator';
$_LANG['addonAA']['pagesLabels']['apiConfiguration']['saveItem']           = 'Save';

$_LANG['addonAA']['apiConfiguration']['messages']['api_connection_success'] = 'Connection established.';


$_LANG['addonAA']['productsConfiguration']['realtimeRegisterSSLProduct']    = 'RealtimeRegisterSSL Product:';
$_LANG['addonAA']['productsConfiguration']['productName']         = 'Product Name:';
$_LANG['addonAA']['productsConfiguration']['customguide']         = 'Processing SSL message:';
$_LANG['addonAA']['productsConfiguration']['issued_ssl_message']  = 'Issued SSL message:';
$_LANG['addonAA']['productsConfiguration']['configurableOptions'] = 'Pricing SAN:';
$_LANG['addonAA']['productsConfiguration']['configurableOptionsWildcard'] = 'Pricing Wildcard SAN:';
$_LANG['addonAA']['productsConfiguration']['configurableOptionsPeriod'] = 'Pricing';
$_LANG['addonAA']['productsConfiguration']['createConfOptions']   = 'Generate';
$_LANG['addonAA']['productsConfiguration']['editPrices']          = 'Edit Prices';
$_LANG['addonAA']['productsConfiguration']['editYears']           = 'Year(s)';
$_LANG['addonAA']['productsConfiguration']['autoSetup']           = 'Auto Setup:';
$_LANG['addonAA']['productsConfiguration']['autoSetupOrder']      = 'Automatically setup the product as soon as an order is placed';
$_LANG['addonAA']['productsConfiguration']['autoSetupPayment']    = 'Automatically setup the product as soon as the first payment is received';
$_LANG['addonAA']['productsConfiguration']['autoSetupOn']         = 'Automatically setup the product when you manually accept a pending order';
$_LANG['addonAA']['productsConfiguration']['autoSetupOff']        = 'Do not automatically setup this product';
$_LANG['addonAA']['productsConfiguration']['months']              = 'Max Months:';
$_LANG['addonAA']['productsConfiguration']['enableSans']          = 'Enable SANs:';
$_LANG['addonAA']['productsConfiguration']['enableSansWildcard']  = 'Enable Wildcard SANs:';
$_LANG['addonAA']['productsConfiguration']['includedSans']        = 'Included Single SANs:';
$_LANG['addonAA']['productsConfiguration']['includedSansWildcard']= 'Included Wildcard SANs:';
$_LANG['addonAA']['productsConfiguration']['status']              = 'Status:';
$_LANG['addonAA']['productsConfiguration']['setForManyProducts']  = 'Set for multiple products';
$_LANG['addonAA']['productsConfiguration']['statusEnabled']       = 'Status Enabled:';
$_LANG['addonAA']['productsConfiguration']['allOrSelectedProducts'] = 'All or selected products:';
$_LANG['addonAA']['productsConfiguration']['selectProducts']      = 'Select products:';
$_LANG['addonAA']['productsConfiguration']['allProducts']         = 'All products';
$_LANG['addonAA']['productsConfiguration']['selectedProducts']    = 'Selected products';
$_LANG['addonAA']['productsConfiguration']['areYouSureManyProducts'] = 'Are you sure you want to use these settings for multiple products?';
$_LANG['addonAA']['productsConfiguration']['doNotAnything']       = 'Do not anything';
$_LANG['addonAA']['productsConfiguration']['enableFor']            = 'Enable for billing cycle/currency';
$_LANG['addonAA']['productsConfiguration']['pricingInclude']       = "Prices include commission";

$_LANG['addonAA']['productsConfiguration']['statusEnable']  = 'Enable';
$_LANG['addonAA']['productsConfiguration']['statusDisable'] = 'Disable';


$_LANG['addonAA']['productsConfiguration']['paymentType']          = 'Payment Type:';
$_LANG['addonAA']['productsConfiguration']['priceAutoDownlaod']    = 'Price Auto Download:';
$_LANG['addonAA']['productsConfiguration']['commission']           = 'Commission[%]:';
$_LANG['addonAA']['productsConfiguration']['paymentTypeFree']      = 'Free';
$_LANG['addonAA']['productsConfiguration']['paymentTypeRecurring'] = 'Recurring';
$_LANG['addonAA']['productsConfiguration']['paymentTypeOneTime']   = 'One Time';

$_LANG['addonAA']['productsConfiguration']['pricing']             = 'Pricing:';
$_LANG['addonAA']['productsConfiguration']['pricingMonthly']      = 'One Time';
$_LANG['addonAA']['productsConfiguration']['pricingQuarterly']    = 'Quarterly';
$_LANG['addonAA']['productsConfiguration']['pricingSemiAnnually'] = 'Semi-Annually';
$_LANG['addonAA']['productsConfiguration']['pricingAnnually']     = 'Annually';
$_LANG['addonAA']['productsConfiguration']['pricingBiennially']   = 'Biennially';
$_LANG['addonAA']['productsConfiguration']['pricingTriennially']  = 'Triennially';

$_LANG['addonAA']['productsConfiguration']['pricingSetupFee']        = 'Setup Fee';
$_LANG['addonAA']['productsConfiguration']['pricingPrice']           = 'Price';
$_LANG['addonAA']['productsConfiguration']['pricingCommissionPrice'] = 'Price With Commission';
$_LANG['addonAA']['productsConfiguration']['pricingEnable']          = 'Enable';

$_LANG['addonAA']['productsConfiguration']['save']         = 'Save';
$_LANG['addonAA']['productsConfiguration']['messages'][''] = '';


$_LANG['addonAA']['productsCreator']['singleProductCreator'] = 'Single Product Importer';
$_LANG['addonAA']['productsCreator']['realtimeRegisterSSLProduct']     = 'RealtimeRegisterSSL Product:';
$_LANG['addonAA']['productsCreator']['productName']          = 'Product Name:';
$_LANG['addonAA']['productsCreator']['customguide']          = 'Processing SSL message:';
$_LANG['addonAA']['productsCreator']['issued_ssl_message']   = 'Issued SSL message:';
$_LANG['addonAA']['productsCreator']['productGroup']         = 'Product Group:';
$_LANG['addonAA']['productsCreator']['autoSetup']            = 'Auto Setup:';
$_LANG['addonAA']['productsCreator']['autoSetupOrder']       = 'Automatically setup the product as soon as an order is placed';
$_LANG['addonAA']['productsCreator']['autoSetupPayment']     = 'Automatically setup the product as soon as the first payment is received';
$_LANG['addonAA']['productsCreator']['autoSetupOn']          = 'Automatically setup the product when you manually accept a pending order';
$_LANG['addonAA']['productsCreator']['autoSetupOff']         = 'Do not automatically setup this product';
$_LANG['addonAA']['productsCreator']['months']               = ' Months:';



$_LANG['addonAA']['productsCreator']['enableSans']   = 'Enable SANs:';
$_LANG['addonAA']['productsCreator']['includedSans'] = 'Included SANs:';

$_LANG['addonAA']['productsCreator']['pricing']             = 'Pricing:';
$_LANG['addonAA']['productsCreator']['pricingMonthly']      = 'One Time';
$_LANG['addonAA']['productsCreator']['pricingQuarterly']    = 'Quarterly';
$_LANG['addonAA']['productsCreator']['pricingSemiAnnually'] = 'Semi-Annually';
$_LANG['addonAA']['productsCreator']['pricingAnnually']     = 'Annually';
$_LANG['addonAA']['productsCreator']['pricingBiennially']   = 'Biennially';
$_LANG['addonAA']['productsCreator']['pricingTriennially']  = 'Triennially';

$_LANG['addonAA']['productsCreator']['pricingSetupFee'] = 'Setup Fee';
$_LANG['addonAA']['productsCreator']['pricingPrice']    = 'Price';
$_LANG['addonAA']['productsCreator']['pricingEnable']   = 'Enable';
$_LANG['addonAA']['productsCreator']['saveSingle']      = 'Import Single Product';

$_LANG['addonAA']['productsCreator']['multipleProductCreator'] = 'Multiple Product Importer';
$_LANG['addonAA']['productsCreator']['saveMultiple']           = 'Import All Products';

$_LANG['addonAA']['productsCreator']['messages']['mass_product_created']    = 'Products has been added as hidden. Go to "Products Configuration" to unhide it. Before that, verify product configuration and set prices.';
$_LANG['addonAA']['productsCreator']['messages']['single_product_created']  = 'Product has been added as hidden. Go to "Products Configuration" to unhide it. Before that, verify product configuration.';
$_LANG['addonAA']['productsCreator']['messages']['no_product_group_found']  = 'No product group found.';
$_LANG['addonAA']['productsCreator']['messages']['api_product_not_chosen']  = 'RealtimeRegisterSSL product not chosen.';
$_LANG['addonAA']['productsCreator']['messages']['api_configuration_empty'] = 'API configuration are empty';

$_LANG['addonAA']['productsConfiguration']['messages']['product_saved']          = 'Product saved.';
$_LANG['addonAA']['productsConfiguration']['messages']['configurable_generated'] = 'Configurable options for product was successfully generated.';

$_LANG['addonAA']['productsConfiguration']['messages']['api_configuration_empty'] = 'API configuration are empty';

$_LANG['addonAA']['userDiscounts']['integrationCode']['header']         = 'Integration Code';
$_LANG['addonAA']['userDiscounts']['pleaseNote']                        = 'Please Note';
$_LANG['addonAA']['userDiscounts']['info']                              = 'To display product prices with additional commission in the Client Area: ';
$_LANG['addonAA']['userDiscounts']['info1']                             = '1. Open the file';
$_LANG['addonAA']['userDiscounts']['info2']                             = '2. Add this code on the top of file';
$_LANG['addonAA']['userDiscounts']['info3']                             = '3. Open the file';
$_LANG['addonAA']['userDiscounts']['info4']                             = '4. Add this code on the top of file';
$_LANG['addonAA']['pagesLabels']['label']['userDiscounts']              = 'Discount Rules';
$_LANG['addonAA']['userDiscounts']['title']                             = 'Discount Rules';
$_LANG['addonAA']['userDiscounts']['addNewDiscountRule']              = 'Add New Rule';
$_LANG['addonAA']['userDiscounts']['editItem']                          = 'Edit';
$_LANG['addonAA']['userDiscounts']['deleteItem']                        = 'Remove';
$_LANG['addonAA']['userDiscounts']['messages']['addSuccess']            = 'Discount rule added successfully.';
$_LANG['addonAA']['userDiscounts']['messages']['removeSuccess']         = 'Discount rule removed successfully.';
$_LANG['addonAA']['userDiscounts']['messages']['updateSuccess']         = 'Discount rule updated successfully.';
$_LANG['addonAA']['userDiscounts']['messages']['clientIDNotProvided']   = 'Client ID has been not provided.';
$_LANG['addonAA']['userDiscounts']['messages']['ruleIDNotProvided']     = 'Rule ID has been not provided.';
$_LANG['addonAA']['userDiscounts']['messages']['productIDNotProvided']  = 'Product ID has been not provided.';
$_LANG['addonAA']['userDiscounts']['messages']['discountNotProvided'] = 'Discount has been not provided.';

$_LANG['addonAA']['userDiscounts']['table']['client']                       = 'Client';
$_LANG['addonAA']['userDiscounts']['table']['product']                      = 'Product';
$_LANG['addonAA']['userDiscounts']['table']['discount']                   = 'Discount[%]';
$_LANG['addonAA']['userDiscounts']['table']['monthly/onetime']              = 'Monthly/One Time';
$_LANG['addonAA']['userDiscounts']['table']['quarterly']                    = 'Quarterly';
$_LANG['addonAA']['userDiscounts']['table']['semiannually']                 = 'Semiannually';
$_LANG['addonAA']['userDiscounts']['table']['annually']                     = 'Annually';
$_LANG['addonAA']['userDiscounts']['table']['biennially']                   = 'Biennially';
$_LANG['addonAA']['userDiscounts']['table']['triennially']                  = 'Triennially';
$_LANG['addonAA']['userDiscounts']['table']['actions']                      = 'Actions';
$_LANG['addonAA']['userDiscounts']['modal']['pleaseSelecetOnePlaceholder']  = 'Please select one...';
$_LANG['addonAA']['userDiscounts']['modal']['selectClientFirstPlaceholder'] = 'Please select a client first...';
$_LANG['addonAA']['userDiscounts']['modal']['pleaseSelectProductFirst']     = 'Please select a product first...';
$_LANG['addonAA']['userDiscounts']['modal']['noDataAvailable']              = 'No data available.';
$_LANG['addonAA']['userDiscounts']['modal']['noClientAvailable']            = 'No client available.';
$_LANG['addonAA']['userDiscounts']['modal']['noProductAvailable']           = 'No product available.';
$_LANG['addonAA']['userDiscounts']['table']['basePrice']                    = 'Price 1 year: ';
$_LANG['addonAA']['userDiscounts']['table']['priceWithDiscount']          = 'With Discount: ';

$_LANG['addonAA']['userDiscounts']['modal']['addCommissionRule']          = 'Add New Commission Rule';
$_LANG['addonAA']['userDiscounts']['modal']['client']                     = 'Client';
$_LANG['addonAA']['userDiscounts']['modal']['product']                    = 'Product';
$_LANG['addonAA']['userDiscounts']['modal']['discount']                   = 'Discount[%]';
$_LANG['addonAA']['userDiscounts']['modal']['add']                        = 'Add';
$_LANG['addonAA']['userDiscounts']['modal']['edit']                       = 'Save Changes';
$_LANG['addonAA']['userDiscounts']['modal']['close']                      = 'Close';
$_LANG['addonAA']['userDiscounts']['modal']['productPrice']               = 'Price 1 Year';
$_LANG['addonAA']['userDiscounts']['modal']['productPriceWithDiscount'] = 'Price 1 Year with discounts';
$_LANG['addonAA']['userDiscounts']['modal']['monthly/onetime']            = 'Monthly/One Time';
$_LANG['addonAA']['userDiscounts']['modal']['quarterly']                  = 'Quarterly';
$_LANG['addonAA']['userDiscounts']['modal']['semiannually']               = 'Semiannually';
$_LANG['addonAA']['userDiscounts']['modal']['annually']                   = 'Annually';
$_LANG['addonAA']['userDiscounts']['modal']['biennially']                 = 'Biennially';
$_LANG['addonAA']['userDiscounts']['modal']['triennially']                = 'Triennially';
$_LANG['addonAA']['userDiscounts']['modal']['removeRule']                 = 'Remove Commission Rule';
$_LANG['addonAA']['userDiscounts']['modal']['remove']                     = 'Remove';
$_LANG['addonAA']['userDiscounts']['modal']['removeRuleInfo']             = 'You are about to remove commission rule, this procedure is irreversible.';


$_LANG['anErrorOccurred'] = 'An error occurred';

$_LANG['pagesLabels']['label']['orders']       = 'Import SSL Order';
$_LANG['addonCA']['sslSummary']['title']       = 'SSL Orders Summary';
$_LANG['addonCA']['sslSummary']['total']       = 'Total Orders';
$_LANG['addonCA']['sslSummary']['unpaid']      = 'Unpaid Orders';
$_LANG['addonCA']['sslSummary']['processing']  = 'Processing';
$_LANG['addonCA']['sslSummary']['expiresSoon'] = 'Expires Soon';

$_LANG['addonCA']['sslSummaryOrdersPage']['pageTitle']['total']        = 'Total Orders';
$_LANG['addonCA']['sslSummaryOrdersPage']['pageTitle']['unpaid']       = 'Unpaid Orders';
$_LANG['addonCA']['sslSummaryOrdersPage']['pageTitle']['processing']   = 'Processing Orders';
$_LANG['addonCA']['sslSummaryOrdersPage']['pageTitle']['expires_soon'] = 'Expires Soon Orders';
$_LANG['addonCA']['sslSummaryOrdersPage']['Product/Service']           = 'Product/Service';
$_LANG['addonCA']['sslSummaryOrdersPage']['Pricing']                   = 'Pricing';
$_LANG['addonCA']['sslSummaryOrdersPage']['Next Due Date']             = 'Next Due Date';
$_LANG['addonCA']['sslSummaryOrdersPage']['Status']                    = 'Status';

$_LANG['invalidEmailAddress']           = 'Email Address is incorrect';
$_LANG['csrCodeGeneraterdSuccessfully'] = 'CSR code has been generated successfully';
$_LANG['invalidCountryCode']            = 'Country code is incorrect';
$_LANG['csrCodeGeneraterFailed']        = 'Generate CSR code has been failed';

$_LANG['viewAll']		= 'View All';
$_LANG['addonAA']['productsConfiguration']['save_all_products']       = 'Save all products';
$_LANG['addonAA']['productsConfiguration']['products_saved']       = 'Products has been saved.';

$_LANG['additionalSingleDomainInfo'] = 'This product has %s additional single domain SAN\'s included.';
$_LANG['additionalSingleDomainWildcardInfo'] = 'This product has %s additional wildcard domain SAN\'s included.';

$_LANG['addonAA']['userDiscounts']['modal']['noClientAvaialblePlaceholder'] = 'No Client Available Placeholder';
$_LANG['addonAA']['userDiscounts']['modal']['noProductAvaialblePlaceholder'] = 'No Product Available Placeholder';
$_LANG['addonAA']['userDiscounts']['modal']['editCommissionRule'] = 'Edit Commission Rule';
$_LANG['addonAA']['pagesLabels']['label']['logs'] = 'Logs';

$_LANG['addonAA']['logs']['title'] = 'Logs';
$_LANG['addonAA']['logs']['table']['id'] = 'Id';
$_LANG['addonAA']['logs']['table']['client'] = 'Client';
$_LANG['addonAA']['logs']['table']['service'] = 'Service';
$_LANG['addonAA']['logs']['table']['type'] = 'Type';
$_LANG['addonAA']['logs']['table']['msg'] = 'Msg';
$_LANG['addonAA']['logs']['table']['date'] = 'Date';
$_LANG['addonAA']['logs']['table']['actions'] = 'Actions';
$_LANG['addonAA']['logs']['deleteItem'] = 'Delete Log';
$_LANG['addonAA']['pagesLabels']['label']['orders'] = 'Orders';
$_LANG['addonAA']['logs']['modal']['removeLog'] = 'Remove Log';
$_LANG['addonAA']['logs']['modal']['removeLogInfo'] = 'Are you sure remove this log?';
$_LANG['addonAA']['logs']['modal']['remove'] = 'Remove';
$_LANG['addonAA']['logs']['modal']['close'] = 'Close';
$_LANG['addonAA']['logs']['messages']['logIDNotProvided'] = 'Log ID Not Provided';
$_LANG['addonAA']['logs']['button']['clear_logs'] = 'Clear logs';
$_LANG['addonAA']['logs']['modal']['clearLogs'] = 'Clear Logs';
$_LANG['addonAA']['logs']['modal']['clearLogsInfo'] = 'Are you sure you want to clear all logs?';
$_LANG['addonAA']['logs']['modal']['Clear'] = 'Clear';
$_LANG['addonAA']['logs']['messages']['clearSuccess'] = 'All logs have been cleared';
$_LANG['addonAA']['logs']['messages']['removeSuccess'] = 'The log has been successfully deleted';

$_LANG['Reissue Certificate'] = 'Reissue Certificate';
$_LANG['serverCA']['home']['Configuration Submitted'] = 'Configuration Submitted';
$_LANG['serverCA']['home']['anErrorOccurred'] = 'An Error Occurred';

$_LANG['addonAA']['orders']['title'] = 'Orders';
$_LANG['addonAA']['orders']['table']['id'] = 'Id';
$_LANG['addonAA']['orders']['table']['client'] = 'Client';
$_LANG['addonAA']['orders']['table']['service'] = 'Service';
$_LANG['addonAA']['orders']['table']['order'] = 'SSL Order';
$_LANG['addonAA']['orders']['table']['verification_method'] = 'Verification method';
$_LANG['addonAA']['orders']['table']['status'] = 'Status';
$_LANG['addonAA']['orders']['table']['date'] = 'Date';
$_LANG['addonAA']['orders']['table']['actions'] = 'Actions';

$_LANG['Choose a domain'] = 'Choose a domain';

$_LANG['addonAA']['apiConfiguration']['cronCertificateInstaller']['pleaseNote'] = 'Please Note:';
$_LANG['addonAA']['apiConfiguration']['cronCertificateInstaller']['info'] = 'In order to install certificate in your hostingpanel, set the following command line cron (once a day suggested):';
$_LANG['addonAA']['apiConfiguration']['cronCertificateInstaller']['commandLine']['cronFrequency'] = '0 0 * * *';

$_LANG['addonAA']['orders']['table']['set as verified'] = 'Set as verified';
$_LANG['addonAA']['orders']['table']['set as installed'] = 'Set as installed';
$_LANG['addonAA']['orders']['messages']['Success'] = 'Success';
