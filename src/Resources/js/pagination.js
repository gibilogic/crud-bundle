$(".pagination input").on("keydown", function(event) {
    if (event.which == 13) {
        var totalPages = parseInt($(this).attr("data-pages"));
        var currentPage = parseInt($(this).attr("data-page"));

        var pageNumber = parseInt($(".pagination input").val());
        if (!pageNumber) {
            pageNumber = 1;
            $(".pagination input").val(1);
        }

        if (pageNumber >= 1 && pageNumber != currentPage && pageNumber <= totalPages) {
            var baseUrl = $(this).closest("ul.pagination").attr("data-baseurl");
            window.location = baseUrl.replace("page=0", "page=" + pageNumber);
        }

        event.preventDefault();
        return false;
    }
});
