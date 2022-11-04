CMS batch migration tool for redirects and short URLs
=====================================================

Each script performs batch "create" operations in Cascade using the [SOAP Web Services API](https://www.hannonhill.com/cascadecms/latest/developing-in-cascade/soap-web-services-api/index.html). Scripts can be run from your local machine.

## Operations:
- Create folders
- Create redirects
- Create short URLs

--- 

### Dependencies:
- Local server environment (e.g. MAMP) with PHP SoapClient module enabled. Depending on your MAMP settings, this should be enabled by default.
- Cascade CMS user account with Administrator privileges.
---

### Usage: 

1. Clone the respository: 

```sh
$ git clone https://github.com/uoitwebteam/ot-cms-web-services-redirects
```

2. Open the **config.php** file in your text editor and update the username/password (user must have Administrator rights). Otherwise the "CMS Administrator" account can be used.

3. Open the relevant script file for the action you want to perform in your text editor and change the variables in the section labelled `Change these variables as needed`. 

Variable | Value type | Description
-------- | ---------- | -----------
`$siteName` |string | The name of the CMS website. 
`$directory` | string | The folder path where you want to create the assets. <br>Note the formatting instructions in the script file for the specific operation. <br>E.g. `Directory name/path within the /redirects folder (no leading slash)`. For example, if the destination of the created assets will be `/redirects/events`, set `$directory = "events"`.
`$assets` | array | Declare the array of assets to be created. <br>Note the formatting instructions in the script file for the specific operation. <br>For example, redirects must have the format `"{Directive} {status} {/requested/path} {https://new.target.url}"`

**Operation-specific variables** 
Operation script | Variable | Value type | Description
---------------- | -------- | ---------- | -----------
create-redirects.php | `$date` | string | Asset expiration date  
create-short-urls.php | `$trackAll` | boolean | Enable default UTM tracking variables on all links. <br>IE, if a URL in `$assets` does not have any UTM parameters, default UTM parameters will be added.
create-short-urls.php | `$metadata` | associative array | Additional metadata values to be added for each URL in `$assets`, such as metadata end dates.


4. To run the script, open the file in your local server's browser.
5. Review the output content. 
	- Ensure the site title and directory are accurate. 
	- If you have included a global expiration date, ensure the date is accurate.
	- Each `$assets` array element will be parsed by the script and the values that will be sent to the CMS will be rendered on the page. Ensure the content is accurate and that no rendering errors exist.
	- For **create-short-urls.php**, additional `utm_medium` and `utm_source` arrays will be output at the bottom of the page. Ensure these follow the guidelines as defined in the [naming conventions for UTM campaign parameters documentation](https://ontariotechu.cascadecms.com/entity/open.act?id=192eafbc0a0000b33bcdc934880e0ffd&type=page). If necessary, update any parameters in the `$assets` array and reload the page to preview the changes.
6. Click **"SUBMIT TO CMS"** to perform the create operation. The web services operation will return *Error* or *Success* messages.
7. Repeat steps 3-6 as required. Click **"TEST NEW CONTENT"** to reload the page with new data after you've made your variable changes in the script file. <br>*** *Note that the submit operation runs based on a `$_POST` request from the **"SUBMIT TO CMS"** click action. If you refresh the page, you may end up re-submitting the `$_POST` request and re-sending the data to the CMS. Either click the **"TEST NEW CONTENT"** button or perform a hard reload of the page instead of refreshing.*