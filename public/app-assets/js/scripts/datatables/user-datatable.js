/*=========================================================================================
    File Name: user-datatable.js
    Description: User Datatable
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy  - Vuejs, HTML & Laravel Admin Dashboard Template
    Version: 2.0
    Author: PIXINVENT
    Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

$(document).ready(function() {
  /************************************************
   *       js of select checkbox and Length        *
   ************************************************/

  $("#check-slct").DataTable({
    dom:
      '<"top"<"actions action-btns"B><"action-filters d-flex justify-content-between"lf>><"clear">rt<"bottom"<"actions">p>',
    columnDefs: [
      {
        orderable: false,
        className: "select-checkbox",
        targets: 0,
        checkboxes: { selectRow: true }
      }
    ],
    select: {
      style: "os",
      style: "multi",
      selector: "td:first-child"
    },
    order: [[1, "asc"]],

    lengthMenu: [[5, 25, 50, -1], [5, 25, 50, "All"]],
    language: {
      search: "_INPUT_",
      searchPlaceholder: "",
      sLengthMenu: "_MENU_"
    }
  })
  // mac chrome checkbox fix
  if (navigator.userAgent.indexOf("Mac OS X") != -1) {
    $(".dt-checkboxes-cell input, .dt-checkboxes").addClass("mac-checkbox")
  }
})
