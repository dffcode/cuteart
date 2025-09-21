import{t as e}from"./toString.bbd66337.js";var r=/[\\^$.*+?()[\]{}|]/g,o=RegExp(r.source);function a(n){return n=e(n),n&&o.test(n)?n.replace(r,"\\$&"):n}export{a as e};
