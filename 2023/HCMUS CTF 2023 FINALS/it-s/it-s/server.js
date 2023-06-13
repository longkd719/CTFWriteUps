const express = require('express');
const ejs = require('ejs');
const path = require('path');
const app = express();
const bodyParser = require('body-parser');
const mongoose = require('mongoose');
const session = require('express-session');
const MongoStore = require('connect-mongo')(session);


const MongoDBURI = process.env.MONGO_URI || 'mongodb://mongo/ManualAuth';

mongoose.connect(MongoDBURI, {
  useUnifiedTopology: true,
  useNewUrlParser: true
});


const User = require('./models/user')
User.findOne({ email: "admin@admin.com" }, (err, data) => {
  if (!data) {
      let newPerson = new User({
        unique_id: 1,
        email: "admin@admin.com",
        username: "admin",
        password: "admin",
        passwordConf: "admin"
      });

      newPerson.save((err, Person) => {
        if (err)
          console.log(err);
        else
          console.log('Success');
      });
  }
});

const db = mongoose.connection;
db.on('error', console.error.bind(console, 'connection error:'));
db.once('open', () => {
});

app.use(session({
  secret: '<Da~ Che>',
  resave: true,
  saveUninitialized: false,
  store: new MongoStore({
    mongooseConnection: db
  })
}));

app.set('views', path.join(__dirname, 'views'));
app.set('view engine', 'ejs');

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: false }));

app.use(express.static(__dirname + '/views'));

const index = require('./routes/index');
app.use('/', index);


app.use((req, res, next) => {
  const err = new Error();
  err.message='File Not Found';
  err.status = 404;
  next(err);
});


app.use((err, req, res, next) => {
  res.status(err.status || 500);
  return res.render('error.ejs',err);
});

 
var port = process.env.PORT || 1337
app.listen(port , () => {
  console.log('Express app listening on port '+port);
});