const searchClient = algoliasearch(algoliaAppID, algoliaSearchKey);

const search = instantsearch({
  indexName: algoliaIndexName,
  searchClient,
  searchFunction(helper) {
    // Ensure we only trigger a search when there's a query
    if (helper.state.query) {
      helper.search();
    }
  },
});

search.addWidgets([
  instantsearch.widgets.searchBox({
    container: "#searchbox",
    placeholder: "Search all site resources",
  }),

  instantsearch.widgets.refinementList({
    container: "#tags-list",
    attribute: "tags",
    limit: 5,
    showMore: true,
  }),

  instantsearch.widgets.hits({
    container: "#hits",
    
    templates: {
      item: `
        <article>
          <a href="{{ permalink }}">
            <strong>
              {{#helpers.highlight}}
                { "attribute": "post_title", "highlightedTagName": "mark" }
              {{/helpers.highlight}}
            </strong>
          </a>
        </article>
      `,
    },
  }),
]);

search.start();
