//document.querySelector('.person[data-chat=person2]').classList.add('active');
//document.querySelector('.chat[data-chat=person2]').classList.add('active-chat');


let friends = {
  list: document.querySelector('ul.people'),
  all: document.querySelectorAll('.left .person'),
  name: '' },

chat = {
  container: document.querySelector('.container .right'),
  current: null,
  person: null,
  name: document.querySelector('.container .right .top .name') };

function updateFriends() {
    friends.all = document.querySelectorAll('.left .person');
    friends.list = document.querySelector('ul.people');
    friends.all.forEach(f => {
        f.addEventListener('mousedown', () => {
          f.classList.contains('active') || setAciveChat(f);
        });
      });
}

friends.all.forEach(f => {
  f.addEventListener('mousedown', () => {
    f.classList.contains('active') || setAciveChat(f);
  });
});

function setAciveChat(f) {
    if (friends.list != null) {
        friends.list.querySelector('.active').classList.remove('active');
        f.classList.add('active');
        f.classList.remove('non-lu');
        chat.current = chat.container.querySelector('.active-chat');

        chat.person = f.getAttribute('data-chat');
        ws.send(JSON.stringify({ type: 'admin', isReadAdmin: true, clientId: chat.person }));
        console.log(chat.person);
        chat.current.classList.remove('active-chat');
        chat.container.querySelector('[data-chat="' + chat.person + '"]').classList.add('active-chat');
        friends.name = f.querySelector('.name').innerText;
        chat.name.innerHTML = friends.name;
    }
  
}