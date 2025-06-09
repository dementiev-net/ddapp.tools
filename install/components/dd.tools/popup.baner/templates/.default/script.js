class Popup {
  // определяем переменные
  conteinerPopup = document.querySelector(".ms-popup");
  conteinerClose = document.querySelector(".ms-popup-close");

  // вызываем конструктор
  constructor() {
    if (this.conteinerPopup) {
      this.cookieCheck();
    }
  }

  // проверяем куку, если куки нет, инициализируем методы
  cookieCheck() {
    let resultCookie = document.cookie.match(/popup=(.+?)(;|$)/);
    if (resultCookie === null) {
      this.open();
      this.close();
      this.bannerClick();
    }
  }

  // записываем куку на 2 часа
  cookie() {
    document.cookie = "popup=true;max-age=" + 3600 * 2;
  }

  // инициализируем баннер
  open() {
    setTimeout(
      function () {
        this.conteinerPopup.style.cssText = "display: block;";
      }.bind(this),
      1000
    );

    setTimeout(
      function () {
        this.conteinerPopup.classList.add("active");
      }.bind(this),
      1500
    );
  }

  // закрываем баннер и записываем куку
  close() {
    this.conteinerClose.addEventListener("click", () => {
      this.cookie();
      this.conteinerPopup.classList.remove("active");
      setTimeout(
        function () {
          this.conteinerPopup.style.cssText = "";
        }.bind(this),
        1000
      );
    });
  }

  // отлавливаем клик перехода и записываем куку
  bannerClick() {
    this.conteinerPopup.addEventListener("click", () => {
      this.cookie();
    });
  }
}

// инициализируем класс
new Popup();
