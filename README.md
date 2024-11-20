# URL Parameter Tracker Plugin

## Description

**URL Parameter Tracker** is a WordPress plugin that tracks URL parameters and autofills them into forms on your website (specifically for integration with Contact Form 7). It allows you to capture UTM parameters or any custom URL parameters and map them to form fields, enhancing lead attribution and user experience.

## Features

- **Track Specific or All URL Parameters**: Choose which URL parameters to track or opt to track all parameters.
- **Autofill Form Fields**: Automatically populate form fields with the tracked parameters.
- **Parameter to Field Mapping**: Define custom mappings between URL parameters and form fields, including options to add labels.
- **Store Session Data**: Save tracked parameters in the database for analysis and record-keeping.
- **View Session Data**: Access a comprehensive table of all collected session data from the WordPress admin dashboard.
- **Delete Session Data**: Manually delete all stored session data with a single click.
- **Automatic Cleanup**: Schedule automatic deletion of session data to manage database size (options: Never, Weekly, Monthly, Yearly).

## Installation

1. **Download the Plugin**:
   - Download the `url-parameter-tracker` plugin folder.

2. **Upload to WordPress**:
   - Upload the `url-parameter-tracker` folder to the `/wp-content/plugins/` directory on your server.

3. **Activate the Plugin**:
   - Log in to your WordPress admin dashboard.
   - Navigate to **Plugins** > **Installed Plugins**.
   - Find **URL Parameter Tracker** in the list and click **Activate**.

## Usage

### Configure Plugin Settings

1. **Access Settings**:
   - In the WordPress admin dashboard, go to **URL Parameter Tracker** > **Settings**.

2. **Tracking Settings**:
   - **Parameters to Track**:
     - Enter a comma-separated list of URL parameters you want to track (e.g., `utm_source, utm_medium`).
     - These parameters will be stored and can be autofilled into forms.
   - **Track All Parameters**:
     - Check this box if you want to track all URL parameters, regardless of the list above.
   - **Cookie Lifetime (days)**:
     - Specify how many days the tracked parameters should be stored in the user's browser cookies.

3. **Parameter to Field Mapping**:
   - Define how tracked parameters map to form fields.
   - Use one mapping per line in the format:
     ```
     parameter=field_selector
     ```
     or with labels:
     ```
     [Label Text]parameter=field_selector
     ```
   - **Examples**:
     ```
     utm_source,utm_medium,utm_campaign=#all-utms
     [Source: ]utm_source,[Medium: ]utm_medium=#utm-parameters
     ```
     - In the first example, values of `utm_source`, `utm_medium`, and `utm_campaign` are inserted into the field with ID `all-utms`.
     - In the second example, labels are added before the values.

4. **Session Data Management**:
   - **Automatic Session Data Cleanup**:
     - Choose how often to automatically delete session data to prevent database growth.
     - Options: Never, Weekly, Monthly, Yearly.

5. **Save Settings**:
   - Click the **Save Changes** button to apply your configurations.

### Mapping Parameters to Form Fields

- Ensure that the form fields you want to autofill have unique selectors (IDs or classes).
- Use the mapping syntax in the **Parameter to Field Mapping** section to link URL parameters to these form fields.

### Viewing and Managing Session Data

1. **Access Session Data**:
   - Navigate to **URL Parameter Tracker** > **Session Data**.
   - View the table displaying user IPs, tracked parameters, and timestamps.

2. **Delete Session Data**:
   - On the **Session Data** page, click the **Delete All Session Data** button to remove all stored data.
   - Confirm the action when prompted.

### Testing the Plugin

- Visit your website with URL parameters (e.g., `https://yourwebsite.com/?utm_source=google&utm_medium=cpc`).
- The parameters should be stored and autofilled into your forms based on your mappings.

## Notes

- **Security Considerations**:
  - All inputs and outputs are sanitized and escaped.
  - Nonces and capability checks are implemented to prevent unauthorized access.

- **Cookie Management**:
  - Tracked parameters are stored in cookies, persisting across pages and sessions based on the specified lifetime.

- **Database Management**:
  - The plugin creates a new database table named `{wp_prefix}_upt_sessions` to store session data.
  - Use the automatic cleanup feature to manage database size effectively.

- **WP-Cron Scheduling**:
  - Automatic cleanup relies on WordPress's WP-Cron system.
  - For sites with low traffic, consider setting up a real cron job to ensure scheduled tasks run reliably.

## Troubleshooting

- **Form Fields Not Autofilling**:
  - Check that the field selectors in your mappings correctly reference the form fields.
  - Ensure that the URL parameters are correctly named and match those specified in your settings.

- **Session Data Not Being Stored**:
  - Verify that the plugin is active and properly configured.
  - Check for JavaScript errors in your browser console that may prevent the script from running.

- **Automatic Cleanup Not Working**:
  - Ensure that WP-Cron is functioning on your site.
  - Use a plugin like **WP Crontrol** to inspect scheduled events.


---

## Automatic Updates

This plugin includes automatic update functionality using the [GitHub Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker). Ensure your plugin stays up to date with the latest features and fixes by leveraging this update checker.

## License

Automated CF7 Export is licensed under the GNU General Public License v3.0. You may obtain a copy of the License at https://www.gnu.org/licenses/gpl-3.0.en.html.

## Credits

This plugin uses the [GitHub Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) to facilitate automatic updates from a GitHub repository.

