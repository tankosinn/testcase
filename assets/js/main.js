// flash message
import FlashMessage from "./utils/FlashMessage";

// input mask
import Inputmask from "inputmask";

export const init = () => {
  document.addEventListener("DOMContentLoaded", function () {
    // get flash messages
    FlashMessage.get();

    // set input masks
    Inputmask({ mask: "+99(999) 999-9999" }).mask(
      document.querySelectorAll("[data-mask-phone]")
    );

    Inputmask({ alias: "email" }).mask(
      document.querySelectorAll("[data-mask-email]")
    );

    Inputmask("99-99-9999").mask(document.querySelectorAll("[data-mask-date]"));
  });
};
