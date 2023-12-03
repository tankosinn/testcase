import { Controller } from "@hotwired/stimulus";

import RequestHandler from "../js/utils/RequestHandler";

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
  static targets = ["formDormitory", "formLoadingDormitory"];

  dormitorySubmit(event) {
    event.preventDefault();

    const formData = new FormData(this.formDormitoryTarget);

    RequestHandler.initLoader(
      this.formDormitoryTarget,
      this.formLoadingDormitoryTarget
    );

    fetch("/yurt/kaydet", {
      method: "POST",
      body: formData,
    })
      .then((response) => RequestHandler.response(response))
      .then((response) => RequestHandler.redirect(response))
      .catch((error) => RequestHandler.error(error))
      .finally(() => {
        RequestHandler.finishLoader(
          this.formDormitoryTarget,
          this.formLoadingDormitoryTarget
        );
      });
  }
}
