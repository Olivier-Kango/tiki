<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>{tr}API Documentation{/tr}</title>
    <link rel="stylesheet" type="text/css" href="{$asset_path}swagger-ui.css" />
    <link rel="icon" type="image/png" href="{$asset_path}favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="{$asset_path}favicon-16x16.png" sizes="16x16" />
    <style>
      html
      {
        box-sizing: border-box;
        overflow: -moz-scrollbars-vertical;
        overflow-y: scroll;
      }

      *,
      *:before,
      *:after
      {
        box-sizing: inherit;
      }

      body
      {
        margin:0;
        background: #fafafa;
      }
    </style>
  </head>

  <body>
    <div id="swagger-ui"></div>

    <script src="{$asset_path}swagger-ui-bundle.js" charset="UTF-8"> </script>
    <script>
    window.onload = function() {
      // Begin Swagger UI call region
      const ui = SwaggerUIBundle({
        url: "{$base_url}api/docs/index.yaml",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
      });
      // End Swagger UI call region

      window.ui = ui;
    };
  </script>
  </body>
</html>