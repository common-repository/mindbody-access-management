(function ($) {
  $(document).ready(
    function ($) {

      // Initialize some variables.
      // Defined in src/Access/AccessDisplay.php.
      var login_nonce = mz_mbo_access_vars.login_nonce,
        // Shortcode atts for current page.
        atts = mz_mbo_access_vars.atts,
        restricted_content = mz_mbo_access_vars.restricted_content,
        number_of_mbo_log_access_checks = 0,
        siteID = mz_mbo_access_vars.siteID;

      var mz_mindbody_access_state = {

        logged_in: (mz_mbo_access_vars.logged_in == 1) ? true : false,
        action: undefined,
        target: undefined,
        siteID: undefined,
        login_nonce: undefined,
        has_access: mz_mbo_access_vars.has_access,
        content: undefined,
        alert_class: undefined,
        spinner: '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>',
        content_wrapper: '<div id="mzAccessContainer"></div>',
        notice_box: $('#mboAccessNotice').html(),
        notice: undefined,
        footer: '<div class="modal__footer" id="loginFooter">\n' +
          '    <a href="https://clients.mindbodyonline.com/ws.asp?&amp;studioid=' + siteID + '>" class="btn btn-primary" id="MBOSite">Visit Mindbody Site</a>\n' +
          '    <a class="btn btn-primary" id="MBOLogout">Logout</a>\n' +
          '</div>\n',
        header: undefined,
        message: undefined,
        client_first_name: undefined,
        client_id: undefined,

        login_form: $('#mzLogInContainer').html(),

        access_container: $('#mzAccessContainer').html(),

        initialize: function (target) {
          this.target = $(target).attr("href");
          this.siteID = $(target).attr('data-siteID');
          this.nonce = $(target).attr("data-nonce");
        }
      };

      /*
      * Render inner content of content wrapper based on state
      */
      function render_mbo_access_activity() {
        // Clear content and content wrapper
        mz_mindbody_access_state.content = '';
        $('#mzAccessContainer').html = '';

        if (mz_mindbody_access_state.action == 'processing') {

          mz_mindbody_access_state.content += mz_mindbody_access_state.spinner;

        } else if (mz_mindbody_access_state.action == 'login_failed') {

          mz_mindbody_access_state.content += mz_mindbody_access_state.login_form;
          mz_mindbody_access_state.content += '<div class="alert alert-warning">' + mz_mindbody_access_state.message + '</div>';

        } else if (mz_mindbody_access_state.action == 'redirect') {

          mz_mindbody_access_state.content += '<div class="alert alert-success">' + mz_mindbody_access_state.message + '</div>';
          mz_mindbody_access_state.content += mz_mindbody_access_state.spinner;

        } else if (mz_mindbody_access_state.action == 'logout') {

          mz_mindbody_access_state.content += '<div class="alert alert-info">' + mz_mindbody_access_state.message + '</div>';
          mz_mindbody_access_state.content += mz_mindbody_access_state.login_form;
          $('#signupModalFooter').remove();

        } else if (mz_mindbody_access_state.action == 'error') {

          mz_mindbody_access_state.content += '<div class="alert alert-danger">' + mz_mindbody_access_state.message + '</div>';

        } else if (mz_mindbody_access_state.action == 'denied') {

          mz_mindbody_access_state.content += mz_mindbody_access_state.message;
          mz_mindbody_access_state.content += mz_mindbody_access_state.footer;

        } else if (mz_mindbody_access_state.action == 'granted') {

          mz_mindbody_access_state.content += '<div class="alert alert-success">' + mz_mindbody_access_state.message + '</div>';
          mz_mindbody_access_state.content += restricted_content;
          mz_mindbody_access_state.content += mz_mindbody_access_state.footer;

        } else {

          // check access
          mz_mbo_access_check_client_access();
        }

        // Render the content to DOM
        if ($('#mzAccessContainer')) {
          $('#mzAccessContainer').html(mz_mindbody_access_state.content);
        }

        // Then reset message
        mz_mindbody_access_state.message = undefined;
      }

      /**
      * Sign In to MBO
      */
      $(document).on(
        'submit', 'form[id="mzLogIn"]', function (ev) {

          ev.preventDefault();

          var form = $(this);
          var formData = form.serializeArray();
          var result = {};
          $.each(
            $('form').serializeArray(), function () {
              result[this.name] = this.value;
            }
          );

          $.ajax(
            {
              dataType: 'json',
              url: mz_mbo_access_vars.ajaxurl,
              type: form.attr('method'),
              context: this, // So we have access to form data within ajax results
              data: {
                action: 'ajax_login_check_access_permissions',
                form: form.serialize(),
                nonce: result.access_permissions_nonce
              },
              beforeSend: function () {
                mz_mindbody_access_state.action = 'processing';
                render_mbo_access_activity();
              },
              success: function (json) {

                if ("success" == json.type) {
                  mz_mindbody_access_state.logged_in = true;
                  mz_mindbody_access_state.client_id = json.client_id;
                  mz_mindbody_access_state.message = json.logged;

                  handle_no_mbo_access(json);
                  handle_granted_mbo_access(json);
                  render_mbo_access_activity();

                } else {
                  mz_mindbody_access_state.action = 'login_failed';
                  mz_mindbody_access_state.message = json.logged;
                  render_mbo_access_activity();
                }
              } // ./ Ajax Success
            }
          ) // End Ajax
            .fail(
              function (json) {
                mz_mindbody_access_state.message = 'ERROR LOGGING IN';
                render_mbo_access_activity();
                console.log(json);
              }
            ); // End Fail

        }
      );

      /**
       * Maybe Redirect
       * @param {string} redirect_url 
       * @returns string|false if is a valid url string
       */
      function mz_mbo_access_maybe_redirect(redirect_url) {
        try {
          $url = new URL(redirect_url);
          if ("null" === $url.origin) {
            return false;
          }
          return $url.href;
        } catch (err) {
          return false;
        }
      }

      /**
       * Handle No MBO Access
       */
      function handle_no_mbo_access(json) {
        if (0 === json.client_access_levels.length) {
          // Client has no access.
          if (null !== mz_mbo_access_vars.denied_redirect && '' !== mz_mbo_access_vars.denied_redirect) {
            // Something is in the shortcode attribute.
            // Does it seem like a working URL?
            try {
              // Make sure it is at least possibly valid.
              let redirect_url = mz_mbo_access_maybe_redirect(mz_mbo_access_vars.denied_redirect);
              if (redirect_url) {
                return window.location.href = redirect_url;
              }

            } catch (err) {
              // Redirect isn't going to work.
            }
          }
          /*
           * Not redirected and no Access.
           */
          mz_mindbody_access_state.action = 'denied';
          mz_mindbody_access_state.message += '</br>';
          mz_mindbody_access_state.message += '<div class="alert alert-warning">' + mz_mbo_access_vars.atts.denied_message + ':';
          mz_mindbody_access_state.message += '<ul>';

          for (let key in mz_mbo_access_vars.required_access_levels) {
            mz_mindbody_access_state.message += '<li>' + mz_mbo_access_vars.required_access_levels[key].access_level_name + '</li>';
          }

          mz_mindbody_access_state.message += '</ul></div>';
        } // End no access.

      }

      /**
       * Handle Granted MBO Access
       */
      function handle_granted_mbo_access(json) {

        /**
         * Loop through shortcode access levels, checking for access.
         * If client has access to a level, see if it's a redirect shortcode
         * and if so redirect.
         */
        for (i = 0; i < atts.access_levels.length; i++) {
          if (json.client_access_levels.indexOf(atts.access_levels[i]) != -1) {
            // Client has access to this level.

            const access_level = mz_mbo_access_vars.all_access_levels[i + 1];
            console.log(access_level);
            if (1 === parseInt(atts.user_login_redirect)) {
              if (0 !== access_level.access_level_redirect_post) {

                // Does it seem like a working URL?
                try {
                  // Make sure it is at least possibly valid.
                  let redirect_url = mz_mbo_access_maybe_redirect(access_level.access_level_redirect_post);
                  console.log(redirect_url);
                  if (redirect_url) {
                    return window.location.href = redirect_url;
                  }

                } catch (err) {
                  // Redirect isn't going to work.
                }
              }
            } // End atts user_login_redirect true.

            /**
             * Either not configured to or not able to redirect,
             * so this must be a page with restricted content on it.
             */

            mz_mindbody_access_state.action = 'granted';
          } // End has access test.
        } // End atts access levels loop.
      }

      /**
       * Check access permissions
       */
      function mz_mbo_access_check_client_access() {
        $.ajax(
          {
            dataType: 'json',
            url: mz_mbo_access_vars.ajaxurl,
            context: this, // So we have access to form data within ajax results
            data: {
              action: 'ajax_login_check_access_permissions',
              nonce: mz_mbo_access_vars.check_logged_nonce
            },
            beforeSend: function () {
              mz_mindbody_access_state.action = 'processing';
              render_mbo_access_activity();
            },
            success: function (json) {
              if ((json.type == "success") && (atts.access_levels.indexOf(String(json.client_access_level)) != -1)) {
                mz_mindbody_access_state.logged_in = true;
                mz_mindbody_access_state.action = 'granted';
                mz_mindbody_access_state.message = json.message;
                render_mbo_access_activity();
              } else {
                mz_mindbody_access_state.action = 'denied';
                mz_mindbody_access_state.message = json.logged + '<div class="alert alert-warning">' + mz_mbo_access_vars.atts.denied_message + ' ' + mz_mbo_access_vars.membership_types + '</div>';
                render_mbo_access_activity();
              }
            } // ./ Ajax Success
          }
        ) // End Ajax
          .fail(
            function (json) {
              mz_mindbody_access_state.message = 'ERROR CHECKING ACCESS';
              render_mbo_access_activity();
              console.log(json);
            }
          ); // End Fail
      }


      /**
       * Logout of MBO
       */
      $(document).on(
        'click', "#MBOLogout", function (ev) {
          ev.preventDefault();
          var nonce = $(this).attr("data-nonce");

          $.ajax(
            {
              dataType: 'json',
              url: mz_mbo_access_vars.ajaxurl,
              data: { action: 'ajax_client_logout', nonce: mz_mbo_access_vars.logout_nonce },
              beforeSend: function () {
                mz_mindbody_access_state.action = 'processing';
                render_mbo_access_activity();
              },
              success: function (json) {
                if (json.type == "success") {
                  mz_mindbody_access_state.logged_in = false;
                  mz_mindbody_access_state.action = 'logout';
                  mz_mindbody_access_state.message = json.message;
                  render_mbo_access_activity();
                } else {
                  mz_mindbody_access_state.action = 'logout_failed';
                  mz_mindbody_access_state.message = json.message;
                  render_mbo_access_activity();
                }
              } // ./ Ajax Success
            }
          ) // End Ajax
            .fail(
              function (json) {
                mz_mindbody_access_state.message = 'ERROR LOGGING OUT';
                render_mbo_access_activity();
                console.log(json);
              }
            ); // End Fail
        }
      );

      /**
       * Continually Check if Client is Logged in and Update Status
       *
       * This asks server to check if session has been set with client info
       * every sixty seconds.
       */
      setInterval(mz_mbo_check_client_logged, 60000);

      function mz_mbo_check_client_logged() {
        // Only do this up to 1000 times or so so it's not pinging server all day
        // The count is vague because it's also updated by check_client_access
        number_of_mbo_log_access_checks++;
        if (number_of_mbo_log_access_checks >= 1000) {
          return;
        }

        //this will repeat every minute
        $.ajax(
          {
            dataType: 'json',
            url: mz_mbo_access_vars.ajaxurl,
            data: { action: 'ajax_check_client_logged', nonce: mz_mbo_access_vars.check_logged_nonce },
            success: function (json) {
              if (json.type == "success") {
                mz_mindbody_access_state.logged_in = (json.message == 1 ? true : false);
              }
            } // ./ Ajax Success
          }
        ); // End Ajax
      }


      /**
       * Check and update Client Access once per hour = 3600000
       */
      setInterval(mz_mbo_update_client_access, 3600000);

      function mz_mbo_update_client_access() {

        if (!mz_mindbody_access_state.logged_in) {
          return;
        }

        // Only do this up to 250 times or so
        number_of_mbo_log_access_checks++;
        if (number_of_mbo_log_access_checks >= 500) {
          return;
        }

        $.ajax(
          {
            dataType: 'json',
            url: mz_mbo_access_vars.ajaxurl,
            context: this, // So we have access to form data within ajax results
            data: {
              action: 'ajax_check_access_permissions',
              nonce: mz_mbo_access_vars.login_nonce,
              client_id: mz_mbo_access_vars.client_id
            },
            success: function (json) {
              if (json.type == "success") {
                if (mz_mindbody_access_state.has_access == false && atts.access_levels.indexOf(String(json.client_access_level)) != -1) {
                  mz_mindbody_access_state.has_access = true;
                  mz_mindbody_access_state.action = 'granted';
                  mz_mindbody_access_state.message = 'Access Granted.';
                  render_mbo_access_activity();
                }
                if (mz_mindbody_access_state.has_access == true && json.client_access_level == 0) {
                  mz_mindbody_access_state.has_access = false;
                  mz_mindbody_access_state.action = 'denied';
                  mz_mindbody_access_state.message = '<div class="alert alert-warning">' + atts.access_expired + '</div>';
                  render_mbo_access_activity();
                }
              }
            } // ./ Ajax Success
          }
        ) // End Ajax
          .fail(
            function (json) {
              mz_mindbody_access_state.message = 'ERROR LOGGING IN';
              console.log(json);
            }
          ); // End Fail

      }
    }
  );
})(jQuery);