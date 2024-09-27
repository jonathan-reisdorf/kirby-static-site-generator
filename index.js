panel.plugin("jr/static-site-generator", {
  fields: {
    staticSiteGenerator: {
      props: {
        label: String,
        endpoint: String,
        help: {
          type: String,
          default:
            "Click the button to generate a static version of the website.",
        },
        progress: {
          type: String,
          default: "Please wait...",
        },
        success: {
          type: String,
          default: "Static site successfully generated",
        },
        error: {
          type: String,
          default: "An error occurred",
        },
      },
      data() {
        return {
          isBusy: false,
          response: null,
        };
      },
      template: `
        <div class="jr-static-site-generator">
          <k-box class="jr-static-site-generator__container" v-if="!response && !isBusy" theme="regular">
            <k-form @submit="execute()">
              <k-text theme="help" class="jr-static-site-generator__help">
              {{ help.replace(/<\\/?p>/g, '') }}
              </k-text>
              <k-button type="submit" icon="upload" theme="negative" class="jr-static-site-generator__execute">
                {{ label }}
              </k-button>
            </k-form>
          </k-box>

          <k-box v-if="isBusy" class="jr-static-site-generator__status" theme="regular">
            <k-text>{{ progress }}</k-text>
          </k-box>
          <k-box v-if="response && response.success" class="jr-static-site-generator__status" theme="positive">
            <k-text>{{ success }}</k-text>
            <k-text v-if="response.message" class="jr-static-site-generator__message" theme="help">{{ response.message }}</k-text>
          </k-box>
          <k-box v-if="response && !response.success" class="jr-static-site-generator__status" theme="negative">
            <k-text>{{ error }}</k-text>
            <k-text v-if="response.message" class="jr-static-site-generator__message" theme="help">{{ response.message }}</k-text>
          </k-box>
        </div>
      `,
      methods: {
        async execute() {
          const { endpoint } = this.$props;
          if (!endpoint) {
            throw new Error(
              'Error: Config option "static_site_generator.endpoint" is missing or null. Please set this to any string, e.g. "generate-static-site".'
            );
          }

          this.isBusy = true;

          const originalFetch = window.fetch;
          let errorResponse;
          window.fetch = async (...args) => {
            const response = await originalFetch(...args);
            response.status >= 400 && (errorResponse = await response.clone());
            return response;
          };

          try {
            const response = await this.$api.post(`${endpoint}`);
            this.response = response;
          } catch {
            errorResponse = (await errorResponse?.text())
              ?.split("FATAL_ERROR:")
              .pop();
            this.response = errorResponse
              ? JSON.parse(errorResponse)
              : { success: false };
          } finally {
            window.fetch = originalFetch;
            this.isBusy = false;
          }
        },
      },
    },
  },
});
