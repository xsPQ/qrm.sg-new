<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>qrm.sg API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8000";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.11.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.11.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-user">
                                <a href="#endpoints-GETapi-user">GET api/user</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-qr--code--download-svg">
                                <a href="#endpoints-GETapi-qr--code--download-svg">GET api/qr/{code}/download.svg</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-qr--code--download-png">
                                <a href="#endpoints-GETapi-qr--code--download-png">GET api/qr/{code}/download.png</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-billing-webhook">
                                <a href="#endpoints-POSTapi-billing-webhook">Dispatch the event with idempotency on the Stripe event id.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-qr-types">
                                <a href="#endpoints-GETapi-qr-types">GET api/qr-types</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-qr-codes-free-tier-status">
                                <a href="#endpoints-GETapi-qr-codes-free-tier-status">Free-Tier status for the Creator/Dashboard Upgrade-Prompt and active-count
display (Pflichtenheft §2.4): resolved tier, active count, limit,
remaining headroom and whether the limit has been reached.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-qr-codes-feature-status">
                                <a href="#endpoints-GETapi-qr-codes-feature-status">Feature-gate flags for the Creator/Edit UI (P2-T07): which capabilities
(custom alias, password protection, branding, download profile) the
user's *current* plan unlocks, plus a deterministic upgrade hint for
Free users.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-qr-codes">
                                <a href="#endpoints-GETapi-qr-codes">GET api/qr-codes</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-qr-codes">
                                <a href="#endpoints-POSTapi-qr-codes">POST api/qr-codes</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-qr-codes--id-">
                                <a href="#endpoints-GETapi-qr-codes--id-">GET api/qr-codes/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-qr-codes--id-">
                                <a href="#endpoints-PUTapi-qr-codes--id-">PUT api/qr-codes/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-qr-codes--id-">
                                <a href="#endpoints-DELETEapi-qr-codes--id-">DELETE api/qr-codes/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-qr-codes--id--stats">
                                <a href="#endpoints-GETapi-qr-codes--id--stats">GET api/qr-codes/{id}/stats</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-qr-codes--id--stats-daily">
                                <a href="#endpoints-GETapi-qr-codes--id--stats-daily">GET api/qr-codes/{id}/stats/daily</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-qr-codes--id--stats-hourly">
                                <a href="#endpoints-GETapi-qr-codes--id--stats-hourly">GET api/qr-codes/{id}/stats/hourly</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-billing-checkout">
                                <a href="#endpoints-POSTapi-billing-checkout">POST api/billing/checkout</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-billing-portal">
                                <a href="#endpoints-POSTapi-billing-portal">POST api/billing/portal</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: July 12, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>qrm.sg — Dynamic QR Code Management API. Create, manage and track QR codes programmatically.</p>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8000</code>
</aside>
<pre><code>## Authentication

All endpoints except `GET /api` and `GET /api/qr-types` require a Bearer token.

**API access** requires a Business plan or Pro plan with the API add-on (+1 €/month).
Create tokens in your account at `/account/api-tokens`.

## Rate Limits

- **Pro:** 60 requests/minute
- **Business:** 300 requests/minute

Rate limit info is sent in `X-RateLimit-*` headers on every response.</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-user">GET api/user</h2>

<p>
</p>



<span id="example-requests-GETapi-user">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/user" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/user"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-user">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-user" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-user"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-user"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-user" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-user">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-user" data-method="GET"
      data-path="api/user"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-user', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-user"
                    onclick="tryItOut('GETapi-user');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-user"
                    onclick="cancelTryOut('GETapi-user');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-user"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/user</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-user"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-user"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-qr--code--download-svg">GET api/qr/{code}/download.svg</h2>

<p>
</p>



<span id="example-requests-GETapi-qr--code--download-svg">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/qr/architecto/download.svg" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr/architecto/download.svg"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-qr--code--download-svg">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-qr--code--download-svg" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-qr--code--download-svg"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-qr--code--download-svg"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-qr--code--download-svg" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-qr--code--download-svg">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-qr--code--download-svg" data-method="GET"
      data-path="api/qr/{code}/download.svg"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-qr--code--download-svg', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-qr--code--download-svg"
                    onclick="tryItOut('GETapi-qr--code--download-svg');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-qr--code--download-svg"
                    onclick="cancelTryOut('GETapi-qr--code--download-svg');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-qr--code--download-svg"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/qr/{code}/download.svg</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-qr--code--download-svg"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-qr--code--download-svg"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="code"                data-endpoint="GETapi-qr--code--download-svg"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-qr--code--download-png">GET api/qr/{code}/download.png</h2>

<p>
</p>



<span id="example-requests-GETapi-qr--code--download-png">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/qr/architecto/download.png" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr/architecto/download.png"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-qr--code--download-png">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-qr--code--download-png" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-qr--code--download-png"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-qr--code--download-png"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-qr--code--download-png" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-qr--code--download-png">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-qr--code--download-png" data-method="GET"
      data-path="api/qr/{code}/download.png"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-qr--code--download-png', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-qr--code--download-png"
                    onclick="tryItOut('GETapi-qr--code--download-png');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-qr--code--download-png"
                    onclick="cancelTryOut('GETapi-qr--code--download-png');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-qr--code--download-png"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/qr/{code}/download.png</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-qr--code--download-png"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-qr--code--download-png"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="code"                data-endpoint="GETapi-qr--code--download-png"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-billing-webhook">Dispatch the event with idempotency on the Stripe event id.</h2>

<p>
</p>

<p>{@inheritdoc}</p>

<span id="example-requests-POSTapi-billing-webhook">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/billing/webhook" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/billing/webhook"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-billing-webhook">
</span>
<span id="execution-results-POSTapi-billing-webhook" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-billing-webhook"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-billing-webhook"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-billing-webhook" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-billing-webhook">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-billing-webhook" data-method="POST"
      data-path="api/billing/webhook"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-billing-webhook', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-billing-webhook"
                    onclick="tryItOut('POSTapi-billing-webhook');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-billing-webhook"
                    onclick="cancelTryOut('POSTapi-billing-webhook');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-billing-webhook"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/billing/webhook</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-billing-webhook"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-billing-webhook"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-qr-types">GET api/qr-types</h2>

<p>
</p>



<span id="example-requests-GETapi-qr-types">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/qr-types" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr-types"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-qr-types">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;types&quot;: [
        {
            &quot;id&quot;: &quot;url&quot;,
            &quot;label&quot;: &quot;URL&quot;,
            &quot;description&quot;: &quot;Open a link&quot;,
            &quot;icon&quot;: &quot;link&quot;,
            &quot;fields&quot;: [
                {
                    &quot;key&quot;: &quot;url&quot;,
                    &quot;label&quot;: &quot;Destination URL&quot;,
                    &quot;type&quot;: &quot;url&quot;,
                    &quot;required&quot;: true,
                    &quot;maxlength&quot;: 2048,
                    &quot;placeholder&quot;: &quot;https://example.com&quot;
                }
            ]
        },
        {
            &quot;id&quot;: &quot;message&quot;,
            &quot;label&quot;: &quot;Message&quot;,
            &quot;description&quot;: &quot;Show text&quot;,
            &quot;icon&quot;: &quot;chat&quot;,
            &quot;fields&quot;: [
                {
                    &quot;key&quot;: &quot;message&quot;,
                    &quot;label&quot;: &quot;Message&quot;,
                    &quot;type&quot;: &quot;textarea&quot;,
                    &quot;required&quot;: true,
                    &quot;maxlength&quot;: 2000,
                    &quot;placeholder&quot;: &quot;What should people see when they scan?&quot;
                }
            ]
        },
        {
            &quot;id&quot;: &quot;redirect&quot;,
            &quot;label&quot;: &quot;Redirect&quot;,
            &quot;description&quot;: &quot;HTTP redirect&quot;,
            &quot;icon&quot;: &quot;arrow&quot;,
            &quot;fields&quot;: [
                {
                    &quot;key&quot;: &quot;target_url&quot;,
                    &quot;label&quot;: &quot;Target URL&quot;,
                    &quot;type&quot;: &quot;url&quot;,
                    &quot;required&quot;: true,
                    &quot;maxlength&quot;: 2048,
                    &quot;placeholder&quot;: &quot;https://example.com/landing&quot;
                },
                {
                    &quot;key&quot;: &quot;redirect_code&quot;,
                    &quot;label&quot;: &quot;Redirect code&quot;,
                    &quot;type&quot;: &quot;select&quot;,
                    &quot;required&quot;: true,
                    &quot;options&quot;: {
                        &quot;301&quot;: &quot;301 &mdash; Permanent&quot;,
                        &quot;302&quot;: &quot;302 &mdash; Found&quot;,
                        &quot;307&quot;: &quot;307 &mdash; Temporary (method preserved)&quot;,
                        &quot;308&quot;: &quot;308 &mdash; Permanent (method preserved)&quot;
                    }
                }
            ]
        },
        {
            &quot;id&quot;: &quot;social&quot;,
            &quot;label&quot;: &quot;Social&quot;,
            &quot;description&quot;: &quot;Profile link&quot;,
            &quot;icon&quot;: &quot;share&quot;,
            &quot;fields&quot;: [
                {
                    &quot;key&quot;: &quot;platform&quot;,
                    &quot;label&quot;: &quot;Platform&quot;,
                    &quot;type&quot;: &quot;select&quot;,
                    &quot;required&quot;: true,
                    &quot;options&quot;: {
                        &quot;linkedin&quot;: &quot;LinkedIn&quot;,
                        &quot;twitter&quot;: &quot;Twitter / X&quot;,
                        &quot;x&quot;: &quot;X&quot;,
                        &quot;instagram&quot;: &quot;Instagram&quot;,
                        &quot;facebook&quot;: &quot;Facebook&quot;,
                        &quot;github&quot;: &quot;GitHub&quot;,
                        &quot;youtube&quot;: &quot;YouTube&quot;,
                        &quot;tiktok&quot;: &quot;TikTok&quot;,
                        &quot;website&quot;: &quot;Website&quot;
                    }
                },
                {
                    &quot;key&quot;: &quot;username&quot;,
                    &quot;label&quot;: &quot;Username / handle&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: true,
                    &quot;maxlength&quot;: 255,
                    &quot;placeholder&quot;: &quot;@handle&quot;
                }
            ]
        },
        {
            &quot;id&quot;: &quot;wifi&quot;,
            &quot;label&quot;: &quot;WiFi&quot;,
            &quot;description&quot;: &quot;Join network&quot;,
            &quot;icon&quot;: &quot;wifi&quot;,
            &quot;fields&quot;: [
                {
                    &quot;key&quot;: &quot;ssid&quot;,
                    &quot;label&quot;: &quot;Network name (SSID)&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: true,
                    &quot;maxlength&quot;: 32
                },
                {
                    &quot;key&quot;: &quot;encryption&quot;,
                    &quot;label&quot;: &quot;Encryption&quot;,
                    &quot;type&quot;: &quot;select&quot;,
                    &quot;required&quot;: true,
                    &quot;options&quot;: {
                        &quot;WPA&quot;: &quot;WPA/WPA2&quot;,
                        &quot;WPA2&quot;: &quot;WPA2&quot;,
                        &quot;WEP&quot;: &quot;WEP&quot;,
                        &quot;none&quot;: &quot;None&quot;
                    }
                },
                {
                    &quot;key&quot;: &quot;password&quot;,
                    &quot;label&quot;: &quot;Password&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: false
                },
                {
                    &quot;key&quot;: &quot;hidden&quot;,
                    &quot;label&quot;: &quot;Hidden network&quot;,
                    &quot;type&quot;: &quot;checkbox&quot;,
                    &quot;required&quot;: false
                }
            ]
        },
        {
            &quot;id&quot;: &quot;crypto&quot;,
            &quot;label&quot;: &quot;Crypto&quot;,
            &quot;description&quot;: &quot;Pay address&quot;,
            &quot;icon&quot;: &quot;coin&quot;,
            &quot;fields&quot;: [
                {
                    &quot;key&quot;: &quot;currency&quot;,
                    &quot;label&quot;: &quot;Currency&quot;,
                    &quot;type&quot;: &quot;select&quot;,
                    &quot;required&quot;: true,
                    &quot;options&quot;: {
                        &quot;BTC&quot;: &quot;Bitcoin&quot;,
                        &quot;ETH&quot;: &quot;Ethereum&quot;,
                        &quot;SOL&quot;: &quot;Solana&quot;,
                        &quot;USDT&quot;: &quot;Tether (USDT)&quot;,
                        &quot;USDC&quot;: &quot;USD Coin&quot;
                    }
                },
                {
                    &quot;key&quot;: &quot;address&quot;,
                    &quot;label&quot;: &quot;Wallet address&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: true,
                    &quot;placeholder&quot;: &quot;Wallet address&quot;
                },
                {
                    &quot;key&quot;: &quot;amount&quot;,
                    &quot;label&quot;: &quot;Amount (optional)&quot;,
                    &quot;type&quot;: &quot;number&quot;,
                    &quot;required&quot;: false,
                    &quot;placeholder&quot;: &quot;0.0&quot;
                },
                {
                    &quot;key&quot;: &quot;label&quot;,
                    &quot;label&quot;: &quot;Label (optional)&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 255
                }
            ]
        },
        {
            &quot;id&quot;: &quot;event&quot;,
            &quot;label&quot;: &quot;Event&quot;,
            &quot;description&quot;: &quot;Calendar entry&quot;,
            &quot;icon&quot;: &quot;calendar&quot;,
            &quot;fields&quot;: [
                {
                    &quot;key&quot;: &quot;title&quot;,
                    &quot;label&quot;: &quot;Event title&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: true,
                    &quot;maxlength&quot;: 255
                },
                {
                    &quot;key&quot;: &quot;start&quot;,
                    &quot;label&quot;: &quot;Starts&quot;,
                    &quot;type&quot;: &quot;datetime-local&quot;,
                    &quot;required&quot;: true
                },
                {
                    &quot;key&quot;: &quot;end&quot;,
                    &quot;label&quot;: &quot;Ends (optional)&quot;,
                    &quot;type&quot;: &quot;datetime-local&quot;,
                    &quot;required&quot;: false
                },
                {
                    &quot;key&quot;: &quot;location&quot;,
                    &quot;label&quot;: &quot;Location (optional)&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 255
                },
                {
                    &quot;key&quot;: &quot;description&quot;,
                    &quot;label&quot;: &quot;Description (optional)&quot;,
                    &quot;type&quot;: &quot;textarea&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 2000
                }
            ]
        },
        {
            &quot;id&quot;: &quot;vcard&quot;,
            &quot;label&quot;: &quot;Contact&quot;,
            &quot;description&quot;: &quot;vCard&quot;,
            &quot;icon&quot;: &quot;user&quot;,
            &quot;fields&quot;: [
                {
                    &quot;key&quot;: &quot;first_name&quot;,
                    &quot;label&quot;: &quot;First name&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: true,
                    &quot;maxlength&quot;: 100
                },
                {
                    &quot;key&quot;: &quot;last_name&quot;,
                    &quot;label&quot;: &quot;Last name&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: true,
                    &quot;maxlength&quot;: 100
                },
                {
                    &quot;key&quot;: &quot;organization&quot;,
                    &quot;label&quot;: &quot;Organization&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 255
                },
                {
                    &quot;key&quot;: &quot;title&quot;,
                    &quot;label&quot;: &quot;Job title&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 255
                },
                {
                    &quot;key&quot;: &quot;email&quot;,
                    &quot;label&quot;: &quot;Email&quot;,
                    &quot;type&quot;: &quot;email&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 255
                },
                {
                    &quot;key&quot;: &quot;phone_mobile&quot;,
                    &quot;label&quot;: &quot;Mobile&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 30
                },
                {
                    &quot;key&quot;: &quot;phone_work&quot;,
                    &quot;label&quot;: &quot;Work phone&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 30
                },
                {
                    &quot;key&quot;: &quot;website&quot;,
                    &quot;label&quot;: &quot;Website&quot;,
                    &quot;type&quot;: &quot;url&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 2048
                },
                {
                    &quot;key&quot;: &quot;address_street&quot;,
                    &quot;label&quot;: &quot;Street&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 255
                },
                {
                    &quot;key&quot;: &quot;address_city&quot;,
                    &quot;label&quot;: &quot;City&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 100
                },
                {
                    &quot;key&quot;: &quot;address_zip&quot;,
                    &quot;label&quot;: &quot;ZIP&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 20
                },
                {
                    &quot;key&quot;: &quot;address_country&quot;,
                    &quot;label&quot;: &quot;Country&quot;,
                    &quot;type&quot;: &quot;text&quot;,
                    &quot;required&quot;: false,
                    &quot;maxlength&quot;: 100
                }
            ]
        }
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-qr-types" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-qr-types"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-qr-types"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-qr-types" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-qr-types">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-qr-types" data-method="GET"
      data-path="api/qr-types"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-qr-types', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-qr-types"
                    onclick="tryItOut('GETapi-qr-types');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-qr-types"
                    onclick="cancelTryOut('GETapi-qr-types');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-qr-types"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/qr-types</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-qr-types"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-qr-types"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-qr-codes-free-tier-status">Free-Tier status for the Creator/Dashboard Upgrade-Prompt and active-count
display (Pflichtenheft §2.4): resolved tier, active count, limit,
remaining headroom and whether the limit has been reached.</h2>

<p>
</p>



<span id="example-requests-GETapi-qr-codes-free-tier-status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/qr-codes/free-tier-status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr-codes/free-tier-status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-qr-codes-free-tier-status">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-qr-codes-free-tier-status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-qr-codes-free-tier-status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-qr-codes-free-tier-status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-qr-codes-free-tier-status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-qr-codes-free-tier-status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-qr-codes-free-tier-status" data-method="GET"
      data-path="api/qr-codes/free-tier-status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-qr-codes-free-tier-status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-qr-codes-free-tier-status"
                    onclick="tryItOut('GETapi-qr-codes-free-tier-status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-qr-codes-free-tier-status"
                    onclick="cancelTryOut('GETapi-qr-codes-free-tier-status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-qr-codes-free-tier-status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/qr-codes/free-tier-status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-qr-codes-free-tier-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-qr-codes-free-tier-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-qr-codes-feature-status">Feature-gate flags for the Creator/Edit UI (P2-T07): which capabilities
(custom alias, password protection, branding, download profile) the
user&#039;s *current* plan unlocks, plus a deterministic upgrade hint for
Free users.</h2>

<p>
</p>



<span id="example-requests-GETapi-qr-codes-feature-status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/qr-codes/feature-status" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr-codes/feature-status"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-qr-codes-feature-status">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-qr-codes-feature-status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-qr-codes-feature-status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-qr-codes-feature-status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-qr-codes-feature-status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-qr-codes-feature-status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-qr-codes-feature-status" data-method="GET"
      data-path="api/qr-codes/feature-status"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-qr-codes-feature-status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-qr-codes-feature-status"
                    onclick="tryItOut('GETapi-qr-codes-feature-status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-qr-codes-feature-status"
                    onclick="cancelTryOut('GETapi-qr-codes-feature-status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-qr-codes-feature-status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/qr-codes/feature-status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-qr-codes-feature-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-qr-codes-feature-status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-qr-codes">GET api/qr-codes</h2>

<p>
</p>



<span id="example-requests-GETapi-qr-codes">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/qr-codes" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr-codes"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-qr-codes">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-qr-codes" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-qr-codes"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-qr-codes"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-qr-codes" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-qr-codes">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-qr-codes" data-method="GET"
      data-path="api/qr-codes"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-qr-codes', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-qr-codes"
                    onclick="tryItOut('GETapi-qr-codes');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-qr-codes"
                    onclick="cancelTryOut('GETapi-qr-codes');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-qr-codes"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/qr-codes</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-qr-codes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-qr-codes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-qr-codes">POST api/qr-codes</h2>

<p>
</p>



<span id="example-requests-POSTapi-qr-codes">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/qr-codes" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"title\": \"b\",
    \"type\": \"wifi\",
    \"content\": [],
    \"alias\": \"b\",
    \"password\": \"]|{+-0pBNvYg\",
    \"expires_at\": \"2052-08-05\",
    \"burn\": false,
    \"max_scans\": 22
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr-codes"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "title": "b",
    "type": "wifi",
    "content": [],
    "alias": "b",
    "password": "]|{+-0pBNvYg",
    "expires_at": "2052-08-05",
    "burn": false,
    "max_scans": 22
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-qr-codes">
</span>
<span id="execution-results-POSTapi-qr-codes" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-qr-codes"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-qr-codes"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-qr-codes" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-qr-codes">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-qr-codes" data-method="POST"
      data-path="api/qr-codes"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-qr-codes', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-qr-codes"
                    onclick="tryItOut('POSTapi-qr-codes');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-qr-codes"
                    onclick="cancelTryOut('POSTapi-qr-codes');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-qr-codes"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/qr-codes</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-qr-codes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-qr-codes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="POSTapi-qr-codes"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="POSTapi-qr-codes"
               value="wifi"
               data-component="body">
    <br>
<p>Example: <code>wifi</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>url</code></li> <li><code>text</code></li> <li><code>email</code></li> <li><code>phone</code></li> <li><code>sms</code></li> <li><code>wifi</code></li> <li><code>crypto</code></li> <li><code>vcard</code></li> <li><code>event</code></li> <li><code>message</code></li> <li><code>redirect</code></li> <li><code>social</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>content</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="content"                data-endpoint="POSTapi-qr-codes"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>alias</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="alias"                data-endpoint="POSTapi-qr-codes"
               value="b"
               data-component="body">
    <br>
<p>Must match the regex /^<a href="[a-zA-Z0-9-]*[a-zA-Z0-9]">a-zA-Z0-9</a>?$/. Must be at least 4 characters. Must not be greater than 32 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-qr-codes"
               value="]|{+-0pBNvYg"
               data-component="body">
    <br>
<p>Must be at least 4 characters. Example: <code>]|{+-0pBNvYg</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>expires_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="expires_at"                data-endpoint="POSTapi-qr-codes"
               value="2052-08-05"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after <code>now</code>. Example: <code>2052-08-05</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>burn</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-qr-codes" style="display: none">
            <input type="radio" name="burn"
                   value="true"
                   data-endpoint="POSTapi-qr-codes"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-qr-codes" style="display: none">
            <input type="radio" name="burn"
                   value="false"
                   data-endpoint="POSTapi-qr-codes"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>max_scans</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="max_scans"                data-endpoint="POSTapi-qr-codes"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Example: <code>22</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>settings</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="settings"                data-endpoint="POSTapi-qr-codes"
               value=""
               data-component="body">
    <br>

        </div>
        </form>

                    <h2 id="endpoints-GETapi-qr-codes--id-">GET api/qr-codes/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-qr-codes--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/qr-codes/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr-codes/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-qr-codes--id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-qr-codes--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-qr-codes--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-qr-codes--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-qr-codes--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-qr-codes--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-qr-codes--id-" data-method="GET"
      data-path="api/qr-codes/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-qr-codes--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-qr-codes--id-"
                    onclick="tryItOut('GETapi-qr-codes--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-qr-codes--id-"
                    onclick="cancelTryOut('GETapi-qr-codes--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-qr-codes--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/qr-codes/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-qr-codes--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-qr-codes--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-qr-codes--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the qr code. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-qr-codes--id-">PUT api/qr-codes/{id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-qr-codes--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8000/api/qr-codes/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"title\": \"b\",
    \"alias\": \"n\",
    \"password\": \"|{+-0pBNvYgx\",
    \"expires_at\": \"2026-07-12T15:44:26\",
    \"burn\": true,
    \"max_scans\": 43,
    \"status\": \"active\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr-codes/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "title": "b",
    "alias": "n",
    "password": "|{+-0pBNvYgx",
    "expires_at": "2026-07-12T15:44:26",
    "burn": true,
    "max_scans": 43,
    "status": "active"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-qr-codes--id-">
</span>
<span id="execution-results-PUTapi-qr-codes--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-qr-codes--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-qr-codes--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-qr-codes--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-qr-codes--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-qr-codes--id-" data-method="PUT"
      data-path="api/qr-codes/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-qr-codes--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-qr-codes--id-"
                    onclick="tryItOut('PUTapi-qr-codes--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-qr-codes--id-"
                    onclick="cancelTryOut('PUTapi-qr-codes--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-qr-codes--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/qr-codes/{id}</code></b>
        </p>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/qr-codes/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-qr-codes--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-qr-codes--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-qr-codes--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the qr code. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="PUTapi-qr-codes--id-"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>content</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="content"                data-endpoint="PUTapi-qr-codes--id-"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>alias</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="alias"                data-endpoint="PUTapi-qr-codes--id-"
               value="n"
               data-component="body">
    <br>
<p>Must match the regex /^<a href="[a-zA-Z0-9-]*[a-zA-Z0-9]">a-zA-Z0-9</a>?$/. Must be at least 4 characters. Must not be greater than 32 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="PUTapi-qr-codes--id-"
               value="|{+-0pBNvYgx"
               data-component="body">
    <br>
<p>Must be at least 4 characters. Example: <code>|{+-0pBNvYgx</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>expires_at</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="expires_at"                data-endpoint="PUTapi-qr-codes--id-"
               value="2026-07-12T15:44:26"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-07-12T15:44:26</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>burn</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-qr-codes--id-" style="display: none">
            <input type="radio" name="burn"
                   value="true"
                   data-endpoint="PUTapi-qr-codes--id-"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-qr-codes--id-" style="display: none">
            <input type="radio" name="burn"
                   value="false"
                   data-endpoint="PUTapi-qr-codes--id-"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>true</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>max_scans</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="max_scans"                data-endpoint="PUTapi-qr-codes--id-"
               value="43"
               data-component="body">
    <br>
<p>Must be at least 1. Example: <code>43</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>settings</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="settings"                data-endpoint="PUTapi-qr-codes--id-"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PUTapi-qr-codes--id-"
               value="active"
               data-component="body">
    <br>
<p>Example: <code>active</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>active</code></li> <li><code>disabled</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-qr-codes--id-">DELETE api/qr-codes/{id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-qr-codes--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8000/api/qr-codes/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr-codes/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-qr-codes--id-">
</span>
<span id="execution-results-DELETEapi-qr-codes--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-qr-codes--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-qr-codes--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-qr-codes--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-qr-codes--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-qr-codes--id-" data-method="DELETE"
      data-path="api/qr-codes/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-qr-codes--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-qr-codes--id-"
                    onclick="tryItOut('DELETEapi-qr-codes--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-qr-codes--id-"
                    onclick="cancelTryOut('DELETEapi-qr-codes--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-qr-codes--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/qr-codes/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-qr-codes--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-qr-codes--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-qr-codes--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the qr code. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-qr-codes--id--stats">GET api/qr-codes/{id}/stats</h2>

<p>
</p>



<span id="example-requests-GETapi-qr-codes--id--stats">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/qr-codes/1/stats" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr-codes/1/stats"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-qr-codes--id--stats">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-qr-codes--id--stats" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-qr-codes--id--stats"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-qr-codes--id--stats"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-qr-codes--id--stats" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-qr-codes--id--stats">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-qr-codes--id--stats" data-method="GET"
      data-path="api/qr-codes/{id}/stats"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-qr-codes--id--stats', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-qr-codes--id--stats"
                    onclick="tryItOut('GETapi-qr-codes--id--stats');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-qr-codes--id--stats"
                    onclick="cancelTryOut('GETapi-qr-codes--id--stats');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-qr-codes--id--stats"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/qr-codes/{id}/stats</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-qr-codes--id--stats"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-qr-codes--id--stats"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-qr-codes--id--stats"
               value="1"
               data-component="url">
    <br>
<p>The ID of the qr code. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-qr-codes--id--stats-daily">GET api/qr-codes/{id}/stats/daily</h2>

<p>
</p>



<span id="example-requests-GETapi-qr-codes--id--stats-daily">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/qr-codes/1/stats/daily" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr-codes/1/stats/daily"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-qr-codes--id--stats-daily">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-qr-codes--id--stats-daily" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-qr-codes--id--stats-daily"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-qr-codes--id--stats-daily"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-qr-codes--id--stats-daily" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-qr-codes--id--stats-daily">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-qr-codes--id--stats-daily" data-method="GET"
      data-path="api/qr-codes/{id}/stats/daily"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-qr-codes--id--stats-daily', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-qr-codes--id--stats-daily"
                    onclick="tryItOut('GETapi-qr-codes--id--stats-daily');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-qr-codes--id--stats-daily"
                    onclick="cancelTryOut('GETapi-qr-codes--id--stats-daily');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-qr-codes--id--stats-daily"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/qr-codes/{id}/stats/daily</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-qr-codes--id--stats-daily"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-qr-codes--id--stats-daily"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-qr-codes--id--stats-daily"
               value="1"
               data-component="url">
    <br>
<p>The ID of the qr code. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-qr-codes--id--stats-hourly">GET api/qr-codes/{id}/stats/hourly</h2>

<p>
</p>



<span id="example-requests-GETapi-qr-codes--id--stats-hourly">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8000/api/qr-codes/1/stats/hourly" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/qr-codes/1/stats/hourly"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-qr-codes--id--stats-hourly">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-qr-codes--id--stats-hourly" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-qr-codes--id--stats-hourly"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-qr-codes--id--stats-hourly"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-qr-codes--id--stats-hourly" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-qr-codes--id--stats-hourly">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-qr-codes--id--stats-hourly" data-method="GET"
      data-path="api/qr-codes/{id}/stats/hourly"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-qr-codes--id--stats-hourly', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-qr-codes--id--stats-hourly"
                    onclick="tryItOut('GETapi-qr-codes--id--stats-hourly');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-qr-codes--id--stats-hourly"
                    onclick="cancelTryOut('GETapi-qr-codes--id--stats-hourly');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-qr-codes--id--stats-hourly"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/qr-codes/{id}/stats/hourly</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-qr-codes--id--stats-hourly"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-qr-codes--id--stats-hourly"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-qr-codes--id--stats-hourly"
               value="1"
               data-component="url">
    <br>
<p>The ID of the qr code. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-billing-checkout">POST api/billing/checkout</h2>

<p>
</p>



<span id="example-requests-POSTapi-billing-checkout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/billing/checkout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"plan\": \"pro\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/billing/checkout"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "plan": "pro"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-billing-checkout">
</span>
<span id="execution-results-POSTapi-billing-checkout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-billing-checkout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-billing-checkout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-billing-checkout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-billing-checkout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-billing-checkout" data-method="POST"
      data-path="api/billing/checkout"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-billing-checkout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-billing-checkout"
                    onclick="tryItOut('POSTapi-billing-checkout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-billing-checkout"
                    onclick="cancelTryOut('POSTapi-billing-checkout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-billing-checkout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/billing/checkout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-billing-checkout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-billing-checkout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>plan</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="plan"                data-endpoint="POSTapi-billing-checkout"
               value="pro"
               data-component="body">
    <br>
<p>Example: <code>pro</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>pro</code></li> <li><code>business</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-billing-portal">POST api/billing/portal</h2>

<p>
</p>



<span id="example-requests-POSTapi-billing-portal">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8000/api/billing/portal" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8000/api/billing/portal"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-billing-portal">
</span>
<span id="execution-results-POSTapi-billing-portal" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-billing-portal"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-billing-portal"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-billing-portal" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-billing-portal">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-billing-portal" data-method="POST"
      data-path="api/billing/portal"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-billing-portal', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-billing-portal"
                    onclick="tryItOut('POSTapi-billing-portal');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-billing-portal"
                    onclick="cancelTryOut('POSTapi-billing-portal');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-billing-portal"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/billing/portal</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-billing-portal"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-billing-portal"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
