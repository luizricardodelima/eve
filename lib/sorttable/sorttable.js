/**
 * Code adapted from https://www.w3schools.com/howto/howto_js_sort_table.asp with some 
 * modifications to sort both numeric and alphanumeric columns
 */
function sortColumn(tableid, n, isnumeric) {
  var i, x, y, shouldSwitch, switchcount = 0;
  var table = document.getElementById(tableid);
  var rows = table.rows;
  var switching = true;
  
  // Default sorting direction is ascending. It changes to "desc" when it is detected
  // that the column is already sorted in ascending order.
  var dir = "asc";
  clearTableHeaders(table);
  table.rows[0].getElementsByTagName("th")[n].textContent += "▼";

  // Make a loop that will continue until no switching has been done
  while (switching) {
    // Start by saying: no switching is done:
    switching = false;
    
    /* Loop through all table rows (except the first, which contains table headers): */
    for (i = 1; i < (rows.length - 1); i++) {
      // Start by saying there should be no switching:
      shouldSwitch = false;
      // Get the two elements you want to compare, one from current row and one from the next: */
      if (isnumeric)
      {
        x = Number(rows[i].getElementsByTagName("TD")[n].innerHTML);
        y = Number(rows[i + 1].getElementsByTagName("TD")[n].innerHTML);
      }
      else
      {
        x = rows[i].getElementsByTagName("TD")[n].innerHTML.toLowerCase();
        y = rows[i + 1].getElementsByTagName("TD")[n].innerHTML.toLowerCase();
      }
      // Check if the two rows should switch place, based on the direction, asc or desc:
      // If so, mark as a switch and break the loop
      if (dir == "asc") {
        if (x > y) {
          shouldSwitch = true;
          break;
        }
      } else if (dir == "desc") {
        if (x < y) {
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      /* If a switch has been marked, make the switch and mark that a switch has been done: */
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switchcount++;
      switching = true;
    } else {
      /* If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again. */
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        clearTableHeaders(table);
        table.rows[0].getElementsByTagName("th")[n].textContent += "▲";
        switching = true;
      }
    }
  }
}
function clearTableHeaders(table) {
  headers = table.rows[0].getElementsByTagName("th");
  console.log(headers);
  for (let index = 0; index < headers.length; index++) {
    var header = headers[index];
    if (header.textContent.endsWith("▲") || header.textContent.endsWith("▼"))
      header.textContent = header.textContent.slice(0, -1);
  }
}