import toastr from "toastr";

export default class FlashMessage {
  static messages = JSON.parse(localStorage.getItem("flashMessages") || "[]");

  static add(type, text, instant = false) {
    if (instant) {
      toastr[type](text);
    } else {
      this.messages.push({ type, text });

      localStorage.setItem("flashMessages", JSON.stringify(this.messages));
    }
  }

  static get() {
    this.messages.forEach((message) => {
      toastr[message.type](message.text);
    });

    this.messages = [];

    localStorage.removeItem("flashMessages");
  }
}
