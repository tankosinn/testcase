import { Controller } from "@hotwired/stimulus";

/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="hello" attribute will cause
 * this controller to be executed. The name "hello" comes from the filename:
 * hello_controller.js -> "hello"
 *
 * Delete this file or adapt it for your use!
 */
export default class extends Controller {
  connect() {
    const body = document.getElementsByTagName("body")[0];

    const menus = document.getElementsByClassName("menu");

    for (var i = 0; i < menus.length; i++) {
      menus[i].addEventListener("click", function () {
        body.classList.toggle("navbar-expanded");
      });
    }

    const navbarExpand = function () {
      if (window.innerWidth < 768) {
        body.classList.remove("navbar-expanded");
      } else {
        body.classList.add("navbar-expanded");
      }
    };

    window.addEventListener("resize", navbarExpand);

    navbarExpand();
  }
}
