<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Lookout API Documentation</title>

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
        var tryItOutBaseUrl = "https://lookout.example.com";
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
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-nws-alerts-active">
                                <a href="#endpoints-GETapi-nws-alerts-active">GET /api/nws/alerts/active — as NWS's /alerts/active.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-nws-alerts--id-">
                                <a href="#endpoints-GETapi-nws-alerts--id-">GET /api/nws/alerts/{id} — as NWS's /alerts/{id}.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-alerts-changes">
                                <a href="#endpoints-GETapi-alerts-changes">Feed of alert changes for syncing a downstream copy of all alerts.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-alerts-county--ugc-">
                                <a href="#endpoints-GETapi-alerts-county--ugc-">GET api/alerts/county/{ugc}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-alerts-points">
                                <a href="#endpoints-GETapi-alerts-points">Handle the retrieval of weather alerts for a specific geographic location based on latitude and longitude.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-geo-points">
                                <a href="#endpoints-GETapi-geo-points">Resolve NWS point metadata for a given latitude/longitude.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-geo-ugc">
                                <a href="#endpoints-GETapi-geo-ugc">Resolve the county UGC code for a given latitude/longitude.</a>
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
        <li>Last updated: September 27, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>https://lookout.example.com</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-nws-alerts-active">GET /api/nws/alerts/active — as NWS&#039;s /alerts/active.</h2>

<p>
</p>



<span id="example-requests-GETapi-nws-alerts-active">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://lookout.example.com/api/nws/alerts/active" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://lookout.example.com/api/nws/alerts/active"
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

<span id="example-responses-GETapi-nws-alerts-active">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">content-type: application/geo+json
cache-control: max-age=30, public
x-ratelimit-limit: 60
x-ratelimit-remaining: 57
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;@context&quot;: [
        &quot;https://geojson.org/geojson-ld/geojson-context.jsonld&quot;,
        {
            &quot;@version&quot;: &quot;1.1&quot;,
            &quot;wx&quot;: &quot;https://api.weather.gov/ontology#&quot;,
            &quot;@vocab&quot;: &quot;https://api.weather.gov/ontology#&quot;
        }
    ],
    &quot;type&quot;: &quot;FeatureCollection&quot;,
    &quot;features&quot;: [],
    &quot;title&quot;: &quot;Current watches, warnings, and advisories&quot;,
    &quot;updated&quot;: &quot;2026-09-27T07:27:29+00:00&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-nws-alerts-active" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-nws-alerts-active"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-nws-alerts-active"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-nws-alerts-active" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-nws-alerts-active">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-nws-alerts-active" data-method="GET"
      data-path="api/nws/alerts/active"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-nws-alerts-active', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-nws-alerts-active"
                    onclick="tryItOut('GETapi-nws-alerts-active');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-nws-alerts-active"
                    onclick="cancelTryOut('GETapi-nws-alerts-active');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-nws-alerts-active"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/nws/alerts/active</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-nws-alerts-active"
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
                              name="Accept"                data-endpoint="GETapi-nws-alerts-active"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-nws-alerts--id-">GET /api/nws/alerts/{id} — as NWS&#039;s /alerts/{id}.</h2>

<p>
</p>



<span id="example-requests-GETapi-nws-alerts--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://lookout.example.com/api/nws/alerts/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://lookout.example.com/api/nws/alerts/architecto"
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

<span id="example-responses-GETapi-nws-alerts--id-">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">content-type: application/problem+json
cache-control: no-cache, private
x-ratelimit-limit: 60
x-ratelimit-remaining: 56
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;correlationId&quot;: &quot;ef12f47c-ace7-4219-80da-8a312291ed81&quot;,
    &quot;title&quot;: &quot;Not Found&quot;,
    &quot;type&quot;: &quot;about:blank&quot;,
    &quot;status&quot;: 404,
    &quot;detail&quot;: &quot;Alert &#039;architecto&#039; was not found.&quot;,
    &quot;instance&quot;: &quot;https://lookout.example.com/api/nws/alerts/architecto&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-nws-alerts--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-nws-alerts--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-nws-alerts--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-nws-alerts--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-nws-alerts--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-nws-alerts--id-" data-method="GET"
      data-path="api/nws/alerts/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-nws-alerts--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-nws-alerts--id-"
                    onclick="tryItOut('GETapi-nws-alerts--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-nws-alerts--id-"
                    onclick="cancelTryOut('GETapi-nws-alerts--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-nws-alerts--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/nws/alerts/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-nws-alerts--id-"
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
                              name="Accept"                data-endpoint="GETapi-nws-alerts--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETapi-nws-alerts--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the alert. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-alerts-changes">Feed of alert changes for syncing a downstream copy of all alerts.</h2>

<p>
</p>

<p>Omit <code>since</code> to get a snapshot of every active alert plus a cursor. Then poll with
<code>since=&lt;cursor&gt;</code>, following <code>hasMore</code> until it is false. Each change carries the alert's
current state (with its counties and zones), so applying a change twice is harmless.
A 410 means the cursor fell outside the retained history; resync from a snapshot.</p>

<span id="example-requests-GETapi-alerts-changes">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://lookout.example.com/api/alerts/changes" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"since\": 27,
    \"limit\": 22
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://lookout.example.com/api/alerts/changes"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "since": 27,
    "limit": 22
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-alerts-changes">
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
<span id="execution-results-GETapi-alerts-changes" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-alerts-changes"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-alerts-changes"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-alerts-changes" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-alerts-changes">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-alerts-changes" data-method="GET"
      data-path="api/alerts/changes"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-alerts-changes', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-alerts-changes"
                    onclick="tryItOut('GETapi-alerts-changes');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-alerts-changes"
                    onclick="cancelTryOut('GETapi-alerts-changes');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-alerts-changes"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/alerts/changes</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-alerts-changes"
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
                              name="Accept"                data-endpoint="GETapi-alerts-changes"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>since</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="since"                data-endpoint="GETapi-alerts-changes"
               value="27"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>27</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>limit</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="limit"                data-endpoint="GETapi-alerts-changes"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Example: <code>22</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-alerts-county--ugc-">GET api/alerts/county/{ugc}</h2>

<p>
</p>



<span id="example-requests-GETapi-alerts-county--ugc-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://lookout.example.com/api/alerts/county/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://lookout.example.com/api/alerts/county/architecto"
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

<span id="example-responses-GETapi-alerts-county--ugc-">
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
<span id="execution-results-GETapi-alerts-county--ugc-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-alerts-county--ugc-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-alerts-county--ugc-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-alerts-county--ugc-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-alerts-county--ugc-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-alerts-county--ugc-" data-method="GET"
      data-path="api/alerts/county/{ugc}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-alerts-county--ugc-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-alerts-county--ugc-"
                    onclick="tryItOut('GETapi-alerts-county--ugc-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-alerts-county--ugc-"
                    onclick="cancelTryOut('GETapi-alerts-county--ugc-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-alerts-county--ugc-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/alerts/county/{ugc}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-alerts-county--ugc-"
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
                              name="Accept"                data-endpoint="GETapi-alerts-county--ugc-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>ugc</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="ugc"                data-endpoint="GETapi-alerts-county--ugc-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-alerts-points">Handle the retrieval of weather alerts for a specific geographic location based on latitude and longitude.</h2>

<p>
</p>

<p>Validates the request parameters to ensure they are within the accepted ranges for latitude (-90 to 90)
and longitude (-180 to 180). If validation passes, it resolves the point's county and forecast zone and
fetches alerts for either. Offshore points have no county, so marine alerts match on the zone (e.g. GMZ557).
In case of an error during processing, an error response is returned.</p>

<span id="example-requests-GETapi-alerts-points">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://lookout.example.com/api/alerts/points" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"lat\": -89,
    \"lon\": -179
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://lookout.example.com/api/alerts/points"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "lat": -89,
    "lon": -179
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-alerts-points">
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
<span id="execution-results-GETapi-alerts-points" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-alerts-points"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-alerts-points"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-alerts-points" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-alerts-points">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-alerts-points" data-method="GET"
      data-path="api/alerts/points"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-alerts-points', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-alerts-points"
                    onclick="tryItOut('GETapi-alerts-points');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-alerts-points"
                    onclick="cancelTryOut('GETapi-alerts-points');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-alerts-points"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/alerts/points</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-alerts-points"
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
                              name="Accept"                data-endpoint="GETapi-alerts-points"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>lat</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lat"                data-endpoint="GETapi-alerts-points"
               value="-89"
               data-component="body">
    <br>
<p>Must be between -90 and 90. Example: <code>-89</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>lon</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lon"                data-endpoint="GETapi-alerts-points"
               value="-179"
               data-component="body">
    <br>
<p>Must be between -180 and 180. Example: <code>-179</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-geo-points">Resolve NWS point metadata for a given latitude/longitude.</h2>

<p>
</p>

<p>Validates that <code>lat</code> is in [-90, 90] and <code>lon</code> is in [-180, 180], then
proxies the request to NwsGeoService::getPointsMetadata(). Results are
cached by the service for 30 days.</p>

<span id="example-requests-GETapi-geo-points">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://lookout.example.com/api/geo/points" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"lat\": -89,
    \"lon\": -179
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://lookout.example.com/api/geo/points"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "lat": -89,
    "lon": -179
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-geo-points">
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
<span id="execution-results-GETapi-geo-points" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-geo-points"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-geo-points"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-geo-points" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-geo-points">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-geo-points" data-method="GET"
      data-path="api/geo/points"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-geo-points', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-geo-points"
                    onclick="tryItOut('GETapi-geo-points');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-geo-points"
                    onclick="cancelTryOut('GETapi-geo-points');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-geo-points"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/geo/points</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-geo-points"
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
                              name="Accept"                data-endpoint="GETapi-geo-points"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>lat</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lat"                data-endpoint="GETapi-geo-points"
               value="-89"
               data-component="body">
    <br>
<p>Must be between -90 and 90. Example: <code>-89</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>lon</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lon"                data-endpoint="GETapi-geo-points"
               value="-179"
               data-component="body">
    <br>
<p>Must be between -180 and 180. Example: <code>-179</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-geo-ugc">Resolve the county UGC code for a given latitude/longitude.</h2>

<p>
</p>

<p>Validates coordinates, normalises them to 5 decimal places (matching the
cache key used internally by NwsGeoService), and extracts the UGC segment
from the county URL returned by the NWS Points API.</p>
<p>Example UGC: <code>TXC121</code> (derived from <code>/zones/county/TXC121</code>).</p>

<span id="example-requests-GETapi-geo-ugc">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://lookout.example.com/api/geo/ugc" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"lat\": -89,
    \"lon\": -179
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://lookout.example.com/api/geo/ugc"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "lat": -89,
    "lon": -179
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-geo-ugc">
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
<span id="execution-results-GETapi-geo-ugc" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-geo-ugc"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-geo-ugc"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-geo-ugc" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-geo-ugc">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-geo-ugc" data-method="GET"
      data-path="api/geo/ugc"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-geo-ugc', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-geo-ugc"
                    onclick="tryItOut('GETapi-geo-ugc');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-geo-ugc"
                    onclick="cancelTryOut('GETapi-geo-ugc');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-geo-ugc"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/geo/ugc</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-geo-ugc"
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
                              name="Accept"                data-endpoint="GETapi-geo-ugc"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>lat</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lat"                data-endpoint="GETapi-geo-ugc"
               value="-89"
               data-component="body">
    <br>
<p>Must be between -90 and 90. Example: <code>-89</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>lon</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="lon"                data-endpoint="GETapi-geo-ugc"
               value="-179"
               data-component="body">
    <br>
<p>Must be between -180 and 180. Example: <code>-179</code></p>
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
