<div class="tot-wrap tot-quickstart-page">
    <?php
    // Fetch object from the tot_get_quickstart_object function
    function tot_get_quickstart_object()
    {
        $tot_options = get_option('tot_options');
        $keys = tot_get_keys();
        $quickstart_obj = [
            'has_license' => is_array($keys) && (tot_keys_work('live') || tot_keys_work('test')),
            'is_activated' => $tot_options['tot_field_checkout_require'] ?? false,
            'use_case' => is_array($keys) && isset($keys['verificationUseCase']) ? $keys['verificationUseCase'] : 'age',
            'is_page_verification_activated' => $tot_options['tot_field_verification_gates_enabled'] ?? false,
            'pages_required_verification' => $tot_options['tot_field_require_verification_for_pages'] ?? null,
            'minimum_age' => $tot_options['tot_field_min_age'] ?? 21,
        ];

        return array_merge($quickstart_obj, get_trial_data_from_keys($keys));
    }

	/**
	 * @return array|int[]
	 */
    function get_trial_data_from_keys($keys)
	{
		$now = time();

		$trial_data = [
			'trial_days_remaining' => 0,
			'trial_hours_remaining' => 0,
			'trial_minutes_remaining' => 0,
			'has_card_on_file' => false,
			'has_extended_trial' => false
		];

        if (is_wp_error($keys) || !is_array($keys)) {
			return $trial_data;
        }

		// free trial
		$freeTrialStartTimestamp = $keys['freeTrialStartTimestamp'] ? floor($keys['freeTrialStartTimestamp'] / 1000) : 0;
		$freeTrialEndTimestamp = $keys['freeTrialEndTimestamp'] ? floor($keys['freeTrialEndTimestamp'] / 1000) : 0;
		$freeTrialDiff = $freeTrialEndTimestamp - $now;
        if ($now < $freeTrialEndTimestamp) {
            $trial_data = [
                'trial_days_remaining' => floor($freeTrialDiff / DAY_IN_SECONDS),
                'trial_hours_remaining' => floor(($freeTrialDiff % DAY_IN_SECONDS) / HOUR_IN_SECONDS),
                'trial_minutes_remaining' => floor(($freeTrialDiff % HOUR_IN_SECONDS) / MINUTE_IN_SECONDS)
            ];
        }

        // live
        $trial_data['has_card_on_file'] = isset($keys['goLiveTimestamp']) && $keys['goLiveTimestamp'] > 0;

        // Assume they've extended their trial at least once if the amount of total trial time is > 6 days.
        $trial_data['has_extended_trial'] = isset($keys['goLiveTimestamp']) && $keys['goLiveTimestamp'] > 0;

        return $trial_data;
    }

    $quickstart = tot_get_quickstart_object();
    $has_license = $quickstart['has_license'];


    function totGenerateImageAnchor($context, $action, $image)
    {
        $origin = tot_production_origin();
        $linkParameters = tot_frontend_link_parameters($context);
        $html = '<a href="' . $origin . '/p/id_verification_wordpress/' . $linkParameters . '#workflows" class="tot-edu-img">';
        $html .= totGenerateImageElement($action, $image);
        $html .= '</a>';
        return $html;
    }

    /**
     * @param $origin
     * @param $pluginVersion
     * @return string
     */
    function totGenerateImageElement($action, $image): string
    {
        $origin = tot_production_origin();
        $pluginVersion = tot_plugin_get_version();
        return '<img src="' . $origin . '/external_assets/wordpress/' . $pluginVersion . '/' . $action . '/' . $image . '"/>';
    }


    function totGenerateAnchorButton($context, $text, $trackableAction = null)
    {
        $origin = tot_production_origin();
        $linkParameters = tot_frontend_link_parameters($context, [
            'send_plugins' => true,
            'extra_params' => totGetUseCaseParamByContext($context)
        ]);

        $class = "tot-cta-button" . ($trackableAction ? ' trackable' : '');
        $dataAction = $trackableAction ? " data-action='$trackableAction' " : '';

        $html = '<a class="' . $class . '" ' . $dataAction . ' id="tot_configure_button" href="' . $origin . '/hq/register/' . $linkParameters . '">';
        $html .= $text;
        $html .= '</a>';
        return $html;
    }

    function totGetUseCaseParamByContext($context): string
    {
        $param = 'verificationUseCase=';
        switch ($context) {
            case 'selectVerifyAgeAtCheckout':
                $param .= 'age';
                break;
            case 'selectVerifyIdentityAtCheckout':
                $param .= 'identity';
                break;
            case 'selectVerifyAccount':
                $param .= 'account';
                break;
        }
        return $param;
    }

	/**
	 * @return array<array{id: int, title: string, requireVerification: bool}>
	 */
    function totGetPagesWithVerificationStatus()
	{
        $query = new WP_Query([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        $requiredVerification = tot_get_quickstart_object()['pages_required_verification'];
        $pages = [];
        foreach ($query->posts as $page){
            $pages[] = [
                'id' => $page->ID,
                'title' => $page->post_title,
                'slug' => $page->post_name,
                'requireVerification' => $requiredVerification && in_array($page->post_name, $requiredVerification)
            ];
        }
        return $pages;
    }
    ?>

    <script>
        // Activate slider
        document.addEventListener("DOMContentLoaded", function () {
            sendViewedQuickStartAnalytics();
            initializeQuickstartForm();
            initializeMinimumAgeEvents();
            initializeToggles();
            initializePagesCheckbox();
        });

        function sendViewedQuickStartAnalytics() {
            const has_licence = <?php echo $has_license ? 'true' : 'false'; ?>;
            !has_licence && window.sendTOTAnalytics('viewed_quickstart');
        }

        function initializeQuickstartForm() {
            // Initial state is set from PHP backend
            const quickstartInitialState = <?php echo json_encode($quickstart); ?>;
            const testQuickstartForm = storeJsonData(quickstartInitialState);
            updateFromJson(quickstartInitialState);

            document.querySelectorAll('#testQuickstartForm input, #testQuickstartForm select').forEach(function (input) {
                input.addEventListener('change', function (event) {
                    event.preventDefault();
                    updateFromInputs(event);
                });
            });

            testQuickstartForm.addEventListener('submit', function (event) {
                event.preventDefault();
                updateFromInputs(event);
            });
        }

        function initializeMinimumAgeEvents() {
            const minimum_age = document.getElementById('minimum_age'),
                minimum_age_submitter = document.getElementById('minimum_age_submitter');

            minimum_age.addEventListener('change', function () {
                minimum_age_submitter.classList.remove('disabled');
                updateQuickStartFromDOM();
            });

            minimum_age_submitter.addEventListener('click', function (event) {
                event.preventDefault();

                if (!minimum_age_submitter.classList.contains('disabled')) {
                    submitChanges();
                    minimum_age_submitter.classList.add('disabled');
                    window.sendTOTAnalytics('set_age');
                }
            })
        }

        function initializeToggles() {
            const switches = document.querySelectorAll('.tot-switch');
            switches.forEach(initializeToggleOfSwitch);
        }

        function initializeToggleOfSwitch(totSwitch) {
            let toggle = totSwitch.querySelector('.toggle'),
                slider = totSwitch.querySelector(".tot-slider"),
                sliderContainer = totSwitch.querySelector(".tot-slider-container");

            sliderContainer.addEventListener("click", function () {
                toggle.checked = !toggle.checked;
                triggerChange();
            });

            function triggerChange() {
                if (toggle.checked) {
                    activate();
                    // Your form submission code can go here
                    updateFromToggle();
                } else {
                    deactivate();
                    // Your form submission code can go here
                    updateFromToggle();
                }
                sendAnalyticsForToggleBtn();
            }

            function deactivate() {
                sliderContainer.classList.remove("on");
                sliderContainer.classList.add("off");
                slider.innerHTML = "OFF";
            }

            function activate() {
                sliderContainer.classList.remove("off");
                sliderContainer.classList.add("on");
                slider.innerHTML = "ON";
            }

            function sendAnalyticsForToggleBtn() {
                const updatedQuickstart = getQuickstartFromDOM();
                const use_case = updatedQuickstart.use_case;
                const is_active = use_case == 'account'
                    ? updatedQuickstart.is_page_verification_activated
                    : updatedQuickstart.is_activated;
                const status = is_active ? 'enabled' : 'disabled';
                const action = status + '_' + 'product' + '_' + use_case;
                window.sendTOTAnalytics(action);
            }

            const input = totSwitch.querySelector('input[type="checkbox"]');
            if (input?.checked) {
                activate();
                updateFromToggle();
            } else {
                deactivate();
                updateFromToggle();
            }

            // Synchronize Slider with isActivatedInput
            toggle.addEventListener('change', updateFromToggle);

            function updateFromToggle(event) {
                const updatedQuickstart = getQuickstartFromDOM();
                storeJsonData(updatedQuickstart);
                updateFromInputs(event, updatedQuickstart);
                submitChanges();
            }
        }

        function updateQuickStartFromDOM() {
            const updatedQuickstart = getQuickstartFromDOM();
            storeJsonData(updatedQuickstart);
        }

        function initializePagesCheckbox() {
            const pageCheckBoxes = document.querySelectorAll('.tot-page-checkbox-wrapper input[type="checkbox"]');
            pageCheckBoxes.forEach(function (pageCheckBox) {
                pageCheckBox.addEventListener('change', function (event) {
                    updateQuickStartFromDOM();
                    submitChanges();
                    window.sendTOTAnalytics('selected_pages');
                });
            });
        }

        function submitChanges() {
            let formData = new FormData(),
                quickstartObj = JSON.stringify(getQuickstartFromDOM());

            // Append Data
            formData.append( 'action', 'tot_quickstart_settings' );
            formData.append( "quickstartObj", quickstartObj);

            // Send Data
            fetch( ajaxurl, {
                method: 'POST',
                body: formData,
            } )
                .then( res => res.text() )
                .then( data => console.log( data ) )
                .catch( err => console.log( err ) );
        }

        function getQuickstartFromDOM() {
            const checkedPages = document.querySelectorAll('.tot-page-checkbox-wrapper input[type="checkbox"]:checked');
            let pagesRequiredVerification = [];
            checkedPages.forEach(function (checkedPage) {
                pagesRequiredVerification.push(checkedPage.dataset.page);
            });

            return {
                has_license: document.getElementById('hasLicenseInput').checked,
                use_case: document.getElementById('verificationUseCase').value,
                is_activated: document.getElementById('enableCheckout').checked,
                is_page_verification_activated: document.getElementById('enableVerificationPage').checked,
                pages_required_verification: pagesRequiredVerification,
                minimum_age: document.getElementById('minimum_age').value || 0,
                trial_days_remaining: parseInt(document.getElementById('trialDaysInput').value || 0),
                trial_hours_remaining: parseInt(document.getElementById('trialHoursInput').value || 0),
                trial_minutes_remaining: parseInt(document.getElementById('trialMinutesInput').value || 0),
                has_card_on_file: document.getElementById('hasCardOnFileInput').checked,
                has_extended_trial: document.getElementById('hasExtendedTrialInput').checked
            };
        }

        function getQuickstartObj(quickstartObj) {
            return quickstartObj || JSON.parse(document.getElementById('testQuickstartForm').dataset.quickstart);
        }

        function selectTopLevelSection(quickstartObj) {
            const hasLicense = quickstartObj.has_license;
            const totEduMainContent = document.getElementById('tot-edu-main-content');
            const totAfterLicense = document.getElementById('tot-after-license');

            if (hasLicense === false) {
                // If has_license is false, show tot-edu-main-content and hide tot-after-license
                totEduMainContent.style.display = 'block';
                totAfterLicense.style.display = 'none';
            } else {
                // If has_license is true, hide tot-edu-main-content and show tot-after-license
                totEduMainContent.style.display = 'none';
                totAfterLicense.style.display = 'block';
            }
        }

        function updatePage(quickstartObj) {
            // Fetch the current state from the form's data attribute
            quickstartObj = getQuickstartObj(quickstartObj);

            selectTopLevelSection(quickstartObj);

            // DOM elements that will be updated
            const toggleSwitch = document.getElementById("enableCheckout");
            const trialInfoDiv = document.querySelector(".tot-trial-info");
            const signUpInfo = document.getElementById("signUpInfo");
            const activationInfo = document.getElementById("activationInfo");
            const whiteGloveSection = document.getElementById("whiteGloveSection");
            const totTrialSection = document.getElementById("totTrialSection");
            const signUpButton = document.getElementById("signUpButton");
            const extendTrialHeading = document.getElementById("extendTrialHeading");
            const extendTrialButton = document.getElementById("extendTrialButton");
            const extendTrialSection= document.getElementById("extendTrialSection");

            // On/Off Slider -- affects visibility of other things!
            activationInfo.style.display = quickstartObj.is_activated ? "none" : ""

            const visibilty = quickstartObj.has_card_on_file ? "none" : "";
            let elements = [totTrialSection];
            for (const i in elements) {
                let element = elements[i];
                if (element && element.style) {
                    element.style.display = visibilty;
                }
            }

            updatePageForVerificationUseCase(quickstartObj);

            if (quickstartObj.has_card_on_file) {
                return;
            } else {
                // Trial Period Information
                let trialInfo = "";
                if (quickstartObj.trial_days_remaining >= 1) {
                    const dayWord = quickstartObj.trial_days_remaining === 1 ? 'Day' : 'Days';
                    trialInfo = `<h3>${quickstartObj.trial_days_remaining} ${dayWord} left on your Free Trial!</h3>`;
                } else if (quickstartObj.trial_hours_remaining >= 1) {
                    const hourWord = quickstartObj.trial_hours_remaining === 1 ? 'Hour' : 'Hours';
                    trialInfo = `<h3>${quickstartObj.trial_hours_remaining} ${hourWord} left on your Free Trial!</h3>`;
                } else if (quickstartObj.trial_minutes_remaining >= 1) {
                    const minuteWord = quickstartObj.trial_minutes_remaining === 1 ? 'Minute' : 'Minutes';
                    trialInfo = `<h3>${quickstartObj.trial_minutes_remaining} ${minuteWord} left on your Free Trial!</h3>`;
                } else {
                    trialInfo = `<h3>Your Free Trial has expired.</h3>`;
                }

                trialInfoDiv.innerHTML = trialInfo;
                const totalHoursRemaining = quickstartObj.trial_days_remaining * 24 + quickstartObj.trial_hours_remaining + quickstartObj.trial_minutes_remaining / 60;
                // Update is_trial_period only when days, hours, and minutes are greater than 0
                quickstartObj.is_trial_period = totalHoursRemaining > 0;

                // Extend Trial Information
                let displayExtensionOptionOnHour = 5;
                if ((totalHoursRemaining <= displayExtensionOptionOnHour) && !quickstartObj.has_card_on_file && !quickstartObj.has_extended_trial) {
                    extendTrialSection.style.display = "block";
                } else {
                    extendTrialSection.style.display = "none";
                }

                // toggleSwitch.disabled = !quickstartObj.is_activated;

                // Card on File Information
                if (signUpButton.style) {
                    signUpButton.style.display = quickstartObj.has_card_on_file ? "none" : "inline-block";
                    if (!quickstartObj.has_card_on_file) {
                        signUpInfo.textContent = "";
                        if (totalHoursRemaining > 47) {
                            signUpInfo.textContent = "We've provided you a free trial to allow you to try out Token of Trust right away! ";
                        }
                        if (totalHoursRemaining) {
                            signUpInfo.textContent += "Sign up now to ensure that Token of Trust remains 'ON' after your Free Trial period.";
                        } else {
                            signUpInfo.textContent = "Signup to activate Token of Trust!";
                        }
                    }
                }
            }
        }

        function updatePageForVerificationUseCase(quickstartObj)
        {
            // Fetch the current state from the form's data attribute
            let classNameOfUseCaseElements = 'verification-use-case',
                useCaseElements = document.getElementsByClassName(classNameOfUseCaseElements),
                elementsToBeDisplayed = document.getElementsByClassName(classNameOfUseCaseElements + ' ' + quickstartObj.use_case);

            [...useCaseElements].map(el => el.style.display = 'none');
            [...elementsToBeDisplayed].map(el => el.style.display = '');
        }

        function storeJsonData(quickstartState) {
            const testQuickstartForm = document.getElementById('testQuickstartForm');
            testQuickstartForm.dataset.quickstart = JSON.stringify(quickstartState);
            return testQuickstartForm;
        }

        function updateFromJson(quickstartObj) {
            quickstartObj = getQuickstartObj(quickstartObj);

            console.log("New values for quickstart.", quickstartObj);
            document.getElementById("hasLicenseInput").checked = quickstartObj.has_license;
            document.getElementById("enableCheckout").checked = quickstartObj.is_activated;
            document.getElementById('isActivatedInput').checked = quickstartObj.is_activated;
            document.getElementById('enableVerificationPage').checked = quickstartObj.is_page_verification_activated;
            document.getElementById('verificationUseCase').value = quickstartObj.use_case;
            document.getElementById('minimum_age').value = quickstartObj.minimum_age;
            document.getElementById('trialDaysInput').value = quickstartObj.trial_days_remaining;
            document.getElementById('trialHoursInput').value = quickstartObj.trial_hours_remaining;
            document.getElementById('trialMinutesInput').value = quickstartObj.trial_minutes_remaining;
            document.getElementById('hasCardOnFileInput').checked = quickstartObj.has_card_on_file;
            document.getElementById('hasExtendedTrialInput').checked = quickstartObj.has_extended_trial;
            updatePage(quickstartObj);
        }

        function updateFromInputs(event, quickstartObj) {
            updateFromJson(quickstartObj || getQuickstartFromDOM());
        }

    </script>
    <form id="quickstartForm" action="admin.php?page=totsettings" method="post">
        <div class="tot-container">
            <div id="tot-edu-main-content" class="tot-left-col">
                <img src="https://tokenoftrust.com/wp-content/uploads/2021/03/cropped-tot_logo_iconAndWordmark.png" alt="Token of Trust"/>
                <h3>Start configuring how you'd like to verify your users</h3>
                <?php //echo totGenerateImageAnchor('get-started', 'selectVerificationWorkflow', 'logo.svg'); ?>
                <br>
                <div class="tot-quickstart-edu-content">
                    <div>
                        <?php echo totGenerateAnchorButton('selectVerifyAgeAtCheckout', 'Age at Checkout', 'clicked_get_started_product_age'); ?>
                        <div class="tot-help-tip tot-help-tip-right">
                            <p>
                                <a href="https://help.tokenoftrust.com/article/781-token-of-trust-age-verification-on-checkout"
                                     target="_blank"
                                     class="trackable"
                                     data-action="clicked_learn_more_age"
                                >
                                    Learn more about age verification on checkout
                                </a>
                            </p>
                        </div>
                    </div>
                    <br>
                    <div>
                        <?php echo totGenerateAnchorButton('selectVerifyIdentityAtCheckout', 'Identity at Checkout', 'clicked_get_started_product_identity'); ?>
                        <div class="tot-help-tip tot-help-tip-right">
                            <p>
                                <a href="https://help.tokenoftrust.com/article/786-token-of-trust-identity-verification-on-checkout-in-woocommerce"
                                   target="_blank"
                                   class="trackable"
                                   data-action="clicked_learn_more_identity"
                                >
                                    Learn more about identity verification on checkout
                                </a>
                            </p>
                        </div>
                    </div>
                    <br>
                    <div>
                        <?php echo totGenerateAnchorButton('selectVerifyAccount', 'Account-based on Signup', 'clicked_get_started_product_account'); ?>
                        <div class="tot-help-tip tot-help-tip-right">
                            <p>
                                <a href="https://help.tokenoftrust.com/article/780-how-does-token-of-trust-verify-accounts-on-wordpress"
                                   target="_blank"
                                   class="trackable"
                                   data-action="clicked_learn_more_account"
                                >
                                    Learn more about account verification
                                </a>
                            </p>
                        </div>
                    </div>
                    <br>
                    <div class="tot-callout-box">
                        <p><b>Looking for a different configuration?</b> <a href="#" id="whiteGloveLink" class="trackable" data-action="clicked_whiteglove">Try our White Glove Setup.</a></p>
                    </div>
                    <br>
                    <a id="tot_configure_manually" class="trackable" data-action="clicked_setup_license_key_manually" href="?page=totsettings_license">Setup license keys manually</a>
                </div>
                <?php
                $origin = tot_production_origin();
                $version = tot_plugin_get_version();
                # TODO: use logo: $img_url = "{$origin}/external_assets/wordpress/{$version}/quickstart/logo.svg";
                $img_url = "{$origin}/external_assets/wordpress/{$version}/welcome/logo.svg";
                $img_tag = "<span class=\"tot-notice-icon\"><img src=\"{$img_url}\"/></span>";
                printf('%s', $img_tag);
                ?>
            </div>
            <div id="tot-after-license" class="tot-left-col">

                <!-- For Verification use case: Age & identity -->
                <div class="verification-use-case age identity">
                    <h2 class="tot-icon">You're almost there!</h2>

                    <h3 class="verification-use-case age">
                        Enable Age Verification at Checkout!
                        <div class="tot-help-tip tot-help-tip-large tot-help-tip-right">
                            <p>
                                <a href="https://help.tokenoftrust.com/article/783-how-do-i-get-started-verifying-age-on-checkout-in-woocommerce"
                                   target="_blank"
                                   class="trackable"
                                   data-action="clicked_learn_get_started_age"
                                >
                                    Learn how to get started with age verification on checkout
                                </a>
                            </p>
                        </div>
                    </h3>

                    <h3 class="verification-use-case identity">
                        Enable Identity Verification at Checkout!
                        <div class="tot-help-tip tot-help-tip-large tot-help-tip-right">
                            <p>
                                <a href="https://help.tokenoftrust.com/article/785-how-do-i-get-started-verifying-identity-on-checkout-in-woocommerce"
                                   target="_blank"
                                   class="trackable"
                                   data-action="clicked_learn_get_started_identity"
                                >
                                    Learn how to get started with identity verification on checkout
                                </a>
                            </p>
                        </div>
                    </h3>

                    <!-- Text Field for 'Minimum Age' -->
                    <div class="verification-use-case age">
                        <label for="minimum_age">Minimum Age: </label>
                        <input type="number" class="tot-min-age-input tot_field_standard" id="minimum_age" name="minimum_age">
                        <a title="Submit minimum age" class="disabled" id="minimum_age_submitter"><span class="dashicons dashicons-saved"></span></a>
                    </div>

                    <!-- On/Off Slider -->
                    <div class="tot-switch">
                        <input type="checkbox" class="toggle" id="enableCheckout" disabled/>
                        <div class="tot-slider-container">
                            <div class="tot-fixed-label on">OFF</div>
                            <div class="tot-fixed-label off">ON</div>
                            <div class="tot-slider off"></div>
                        </div>
                    </div>
                    <p id="activationInfo">
                        Select 'ON' to activate Token of Trust on checkout.
                    </p>
                </div>

                <!-- For Verification use case: Account -->
                <div class="verification-use-case account">
                    <h2 class="tot-icon">ID Verification for Pages</h2>

                    <h3>
                        Require ID Verification to access important pages!
                        <div class="tot-help-tip tot-help-tip-large tot-help-tip-right">
                            <p>
                                <a href="https://help.tokenoftrust.com/article/776-how-do-i-get-started-with-gates-on-wordpress"
                                   target="_blank"
                                   class="trackable"
                                   data-action="clicked_learn_get_started_account"
                                >
                                    Learn how to get started with account verification
                                </a>
                            </p>
                        </div>
                    </h3>

                    <!-- On/Off Slider -->
                    <div class="tot-switch">
                        <input type="checkbox" class="toggle" id="enableVerificationPage" disabled/>
                        <div class="tot-slider-container">
                            <div class="tot-fixed-label on">OFF</div>
                            <div class="tot-fixed-label off">ON</div>
                            <div class="tot-slider off"></div>
                        </div>
                    </div>
                    <h3>Select the pages that<br>that require ID Verification:</h3>

                    <div class="tot-callout-box">
						<?php foreach (totGetPagesWithVerificationStatus() as $page): ?>
                        <div class="tot-page-checkbox-wrapper">
                            <input type="checkbox" data-page="<?php echo $page['slug']; ?>"
                                   id="page-checkbox-<?php echo $page['id']; ?>"
                                   <?php checked($page['requireVerification']); ?>>
							<label for="page-checkbox-<?php echo $page['id']; ?>"><?php echo $page['title']; ?></label>
                        </div>

                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="totTrialSection" class="tot-callout-box">
                    <!-- Trial Period Information -->
                    <div class="tot-trial-info"></div>

                    <!-- Card on File Information -->
                    <p id="signUpInfo"></p>
                    <a id="signUpButton" class="tot-cta-button trackable" data-action="clicked_sign_up_now"
                       href="<?php echo tot_production_origin(); ?>/hq/org/plan<?php echo tot_frontend_link_parameters('sign-up', ['send_plugins' => true, 'page' => 'totsettings_quickstart']); ?>"
                       target="_blank">Sign up Now!</a>
                    <!-- Extend Trial Information -->
                    <div id="extendTrialSection">
                        <h3 id="extendTrialHeading">Need More Time?</h3>
                        <a class="tot-cta-button" id="extendTrialButton"
                           href="<?php echo tot_production_origin(); ?>/hq/org/plan<?php echo tot_frontend_link_parameters('extend-trial', ['send_plugins' => true, 'page' => 'totsettings_quickstart']); ?>"
                           target="_blank">Extend your Free Trial *</a>
                        <p>* Requires putting a card on file.</p>
                    </div>
                </div>
            </div>
            <div class="tot-right-col">
                <?php
                require('common/tot-quickstart-support.php');
                tot_generate_support();
                ?>
            </div>
        </div>
    </form>

    <style>

        .tot-quickstart-page .tot-quickstart-edu-content {
            text-align: center;
        }

        .tot-quickstart-page .tot-quickstart-edu-content  .tot-cta-button {
            min-width: 250px;
        }

        .tot-quickstart-page .tot-quickstart-edu-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: -20px;
        }

        .tot-callout-box {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            background-color: #f9f9f9;
            text-align: center;
        }

        #testQuickstartForm {
            width: 50%;
            margin: 40px auto 0;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0px 4px 6px #aaa;
        }

        #testQuickstartForm label {
            display: block;
            margin-bottom: 12px;
            padding: 8px;
            background-color: #fff;
            border-radius: 5px;
        }

        #testQuickstartForm input[type="checkbox"],
        #testQuickstartForm input[type="number"] {
            margin-left: 10px;
        }

        #testQuickstartForm input[type="submit"],
        #testQuickstartForm button {
            margin-top: 12px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }

        #testQuickstartForm button {
            background-color: #ccc;
        }
        .tot-page-checkbox-wrapper {
            display: block;
            margin-left: 5%;
            margin-bottom: 15px;
            text-align: left;
        }

        .tot-page-checkbox-wrapper input {
            padding: 0;
            height: initial;
            width: initial;
            margin-bottom: 0;
            display: none;
            cursor: pointer;
        }

        .tot-page-checkbox-wrapper label {
            position: relative;
            cursor: pointer;
        }

        .tot-page-checkbox-wrapper label:before {
            content:'';
            -webkit-appearance: none;
            background-color: transparent;
            border: 2px solid #0079bf;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05), inset 0px -15px 10px -12px rgba(0, 0, 0, 0.05);
            padding: 10px;
            display: inline-block;
            position: relative;
            vertical-align: middle;
            cursor: pointer;
            margin-right: 10px;
        }

        .tot-page-checkbox-wrapper input:checked + label:after {
            content: '';
            display: block;
            position: absolute;
            top: 8px;
            left: 13px;
            width: 6px;
            height: 14px;
            border: solid #0079bf;
            border-width: 0 3px 3px 0;
            transform: rotate(45deg);
        }
        #minimum_age{
            padding-right: 0;
            padding-left: 3px;
        }
        #minimum_age_submitter {
            text-decoration: none;
            background: green;
            display: inline-flex;
            color: white;
            border-radius: 20px;
            vertical-align: bottom;
            padding: 5px;
            box-shadow: 0px 0px 2px 0px #444;
            margin-left: 5px;
            cursor: pointer;
        }
        #minimum_age_submitter:hover {
            background: #21aa21;
            color: white;
        }
        #minimum_age_submitter.disabled{
            box-shadow: unset;
            background-color: #cccccc;
            color: #666666;
            cursor: default;
        }
</style>

    <?php
    require('view-quickstart-modal.php')
    ?>


    <form id="testQuickstartForm" style="<?php if (!tot_is_dev_env()) : ?> display: none; <?php endif; ?>">
        <h2>Development/Test Configuration</h2>
        <p>Note: this affects the User Experience only and does not actually change configurations on the server side.</p>
        <label>
            Has License
            <input type="checkbox" id="hasLicenseInput" name="hasLicenseInput">
        </label><br>

        <label for="verificationUseCase">Verification Use Case:</label>
        <select id="verificationUseCase" name="verificationUseCase">
            <option value="age">Age</option>
            <option value="identity">Identity</option>
            <option value="account">Account</option>
        </select>

        <label>
            Is Activated:
            <input type="checkbox" id="isActivatedInput" name="isActivatedInput">
        </label><br>

        <label>
            Trial Days Remaining:
            <input type="number" id="trialDaysInput" name="trialDaysInput">
        </label><br>
        <label>
            Trial Hours Remaining:
            <input type="number" id="trialHoursInput" name="trialHoursInput">
        </label><br>
        <label>
            Trial Minutes Remaining:
            <input type="number" id="trialMinutesInput" name="trialMinutesInput">
        </label><br>
        <label>
            Has Card On File:
            <input type="checkbox" id="hasCardOnFileInput" name="hasCardOnFileInput">
        </label><br>
        <label>
            Has Extended Trial:
            <input type="checkbox" id="hasExtendedTrialInput" name="hasExtendedTrialInput">
        </label><br>
        <input type="submit" value="Update">
        <button type="button" onclick="updatePage(document.quickstartInitialState);">Reset</button>
    </form>
</div>
