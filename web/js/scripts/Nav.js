import React from 'react';
import { Grid, Header, Table } from 'semantic-ui-react';

const moment = require('moment');

export default class Nav extends React.Component {
    render() {
        let wkStart = moment(this.props.date).startOf('week').add(1, 'day').format('YYYYMMDD');
        let monthStart = moment(this.props.date).startOf('month').format('YYYYMMDD');
        let todayLink = "/";
        let weekLink = "/wk/" + wkStart;
        let monthLink = "/month/" + monthStart;
        return (
            <div className="left-nav">
                <Grid>
                    <Grid.Row>
                        courses
                    </Grid.Row>
                    <Grid.Row>
                        students
                    </Grid.Row>
                    <Grid.Row>
                        <a href={ todayLink }>today</a>
                    </Grid.Row>
                    <Grid.Row>
                        <a href={ weekLink }>week</a>
                    </Grid.Row>
                    <Grid.Row>
                        <a href={ monthLink }>month</a>
                    </Grid.Row>
                </Grid>
            </div>
        );
    }
}
