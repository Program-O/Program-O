const speak = function (message) {
    if ("speechSynthesis" in window) {
        const msg = new SpeechSynthesisUtterance(message);
        msg.voice = window.speechSynthesis.getVoices()[0];
        window.speechSynthesis.speak(msg);
    }
};