import React from 'react';
import { BrowserRouter as Router, Route, Link, Redirect } from 'react-router-dom';
import Home from './Home';
import Week from './Week';
import Month from './Month';

const dataModel = JSON.parse(document.getElementById("content").dataset.model);

class App extends React.Component {
    render() {
        return (
          <div className="App">
            <header className="App-header">
              <Router>
                <Route path="/dt/:dt" exact render={(props) => {return <Home { ...props } { ...dataModel } />}} />
                <Route path="/wk/:dt" exact render={(props) => {return <Week { ...props } { ...dataModel } />}} />
                <Route path="/month/:dt" exact render={(props) => {return <Month { ...props } { ...dataModel } />}} />
                <Route path="/app_dev.php/dt/:dt" exact render={(props) => {return <Home { ...props } { ...dataModel } />}} />
                <Route path="/" exact render={(props) => {return <Home { ...props } { ...dataModel } />}} />
              </Router>
            </header>
          </div>
        )
    }
}

export default App;