import RequestError from "../errors/RequestError";
import FlashMessage from "./FlashMessage";

export default class RequestHandler {
  static error(error) {
    if (error instanceof RequestError) {
      for (let field in error.errors) {
        const elements = document.querySelectorAll(
          `[data-error-expose="${field}"]`
        );

        elements.forEach((element) => {
          const errorTargets = document.querySelectorAll(
            element.getAttribute("data-error-target")
          );

          errorTargets.forEach((errorTarget) => {
            errorTarget.classList.add("error-highlight");
          });

          for (let i = 0; i < error.errors[field].length; i++) {
            const message = document.createElement("div");

            message.innerHTML = error.errors[field][i].message;

            message.classList.add("error-message");

            element.appendChild(message);
          }
        });
      }

      FlashMessage.add(error.status, error.message, true);
    } else {
      console.error(error);

      FlashMessage.add("error", error.message, true);
    }
  }

  static async response(response) {
    const data = await response.json();

    const errorTargets = document.querySelectorAll(".error-highlight");

    errorTargets.forEach((errorTarget) => {
      errorTarget.classList.remove("error-highlight");
    });

    const elements = document.querySelectorAll("[data-error-expose]");

    elements.forEach((element) => {
      element.innerHTML = "";
    });

    if (
      !response.ok ||
      (data.status !== undefined && data.status === "error")
    ) {
      throw new RequestError(data);
    }

    FlashMessage.add(data.status, data.message);

    return data;
  }

  static initLoader(hide, show) {
    show.classList.remove("d-none");
    hide.classList.add("d-none");
  } 

  static finishLoader(show, hide) {
    show.classList.remove("d-none");
    hide.classList.add("d-none");
  }

  static redirect(response) {
    if (response.slug) {
      const currentUrl = window.location.href;
      const urlParts = currentUrl.split("/");
      urlParts[urlParts.length - 1] = response.slug;
      const url = urlParts.join("/");

      window.location.href = url;
    } else {
      window.location.reload();
    }
  }
}
