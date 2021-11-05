import { registerApplication, start } from "single-spa";

window.registerApplication = registerApplication;

registerApplication({
  name: "@vue-mf/kanban",
  app: () => System.import("@vue-mf/kanban"),
  activeWhen: (location) => {
    let condition = true;
    return condition;
  },
  // Custom data
  // customProps: {
  //   kanbanData: [],
  // },
});

start();