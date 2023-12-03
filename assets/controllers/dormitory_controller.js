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
  static targets = [
    "formRoom",
    "formLoadingRoom",
    "formStudent",
    "formLoadingStudent",
    "formItem",
    "formLoadingItem",
    "formRoomInventory",
    "formLoadingRoomInventory",
  ];

  roomSubmit(event) {
    event.preventDefault();

    const formData = new FormData(this.formRoomTarget);

    RequestHandler.initLoader(this.formRoomTarget, this.formLoadingRoomTarget);

    fetch("/oda/kaydet", {
      method: "POST",
      body: formData,
    })
      .then((response) => RequestHandler.response(response))
      .then((response) => RequestHandler.redirect(response))
      .catch((error) => RequestHandler.error(error))
      .finally(() => {
        RequestHandler.finishLoader(
          this.formRoomTarget,
          this.formLoadingRoomTarget
        );
      });
  }

  studentSubmit(event) {
    event.preventDefault();

    const formData = new FormData(this.formStudentTarget);

    RequestHandler.initLoader(
      this.formStudentTarget,
      this.formLoadingStudentTarget
    );

    fetch("/konuk/kaydet", {
      method: "POST",
      body: formData,
    })
      .then((response) => RequestHandler.response(response))
      .then((response) => RequestHandler.redirect(response))
      .catch((error) => RequestHandler.error(error))
      .finally(() => {
        RequestHandler.finishLoader(
          this.formStudentTarget,
          this.formLoadingStudentTarget
        );
      });
  }

  itemSubmit(event) {
    event.preventDefault();

    const formData = new FormData(this.formItemTarget);

    RequestHandler.initLoader(this.formItemTarget, this.formLoadingItemTarget);

    fetch("/envanter/kaydet", {
      method: "POST",
      body: formData,
    })
      .then((response) => RequestHandler.response(response))
      .then((response) => RequestHandler.redirect(response))
      .catch((error) => RequestHandler.error(error))
      .finally(() => {
        RequestHandler.finishLoader(
          this.formItemTarget,
          this.formLoadingItemTarget
        );
      });
  }

  roomInventorySubmit(event) {
    event.preventDefault();

    const formData = new FormData(this.formRoomInventoryTarget);

    RequestHandler.initLoader(
      this.formRoomInventoryTarget,
      this.formLoadingRoomInventoryTarget
    );

    fetch("/oda/envanter/kaydet", {
      method: "POST",
      body: formData,
    })
      .then((response) => RequestHandler.response(response))
      .then((response) => RequestHandler.redirect(response))
      .catch((error) => RequestHandler.error(error))
      .finally(() => {
        RequestHandler.finishLoader(
          this.formRoomInventoryTarget,
          this.formLoadingRoomInventoryTarget
        );
      });
  }
}
