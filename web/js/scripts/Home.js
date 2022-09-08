import React from 'react';
import { Grid, Table, Input, Dropdown } from 'semantic-ui-react';
import Nav from './Nav';
import TopNav from './TopNav';
import tableHelper from './TableHelper';
import { getData, postData } from './DataManager';

const moment = require('moment');

export default class Home extends React.Component {
    constructor(props) {
        super(props);

        this.displayInput = this.displayInput.bind(this);
        this.displayDropdown = this.displayDropdown.bind(this);
        this.editingOn = this.editingOn.bind(this);

        let dt = props.match.params.dt || moment().format('YYYYMMDD');
        this.state = {
            data: [],
            date: dt,
            pid: 1,
            columns: [],
            subjects: [],
            editing: false,
        };
    }

    componentDidMount() {
        this.handleData();
    }

    handleData() {
        var url = '/acad/pd/' + this.state.pid + '/' + this.state.date;
        getData(url)
        .then(data => {
            if (data.data.table.rows.length > 0) {
                let subjects = [];
                data.data.table.rows.forEach ((r, k) => {
                    subjects.push({
                        key: k,
                        text: r.subject,
                        value: r.subject,
                    });
                });
                this.setState({
                    personId: 1,
                    data: data.data.table.rows,
                    columns: data.data.table.columns,
                    subjects: subjects,
                });
            }
            console.log(this.state)
            // this.loading(false);
        });
    }

    update(v, col, allVals) {
        if (this.state.editing) {
            var data = {student_course_id: allVals.student_course_id, col: col, val: v};
            var url = '/app_dev.php/acad/pd-update/' + this.state.pid + '/' + this.state.date;
            postData(url, data);
            this.setState({ editing: false })
        }
    }

    editingOn(e) {
        this.setState({ editing: true });
    }

    displayInput(vals, rowIdx, colIdx) {
        let colName = this.state.columns[colIdx]['uid'];
        return (
            <Table.Cell key={colName + '_' + rowIdx + '_' + colIdx}>
                <Input
                    defaultValue={vals[colName]}
                    onChange={this.editingOn}
                    onBlur={(x) => {this.update(x.target.value, colName, vals)}}
                />
            </Table.Cell>
        )
    }

    displayDropdown(vals, rowIdx, colIdx) {
        let colName = this.state.columns[colIdx]['uid'];
        return (
            <Table.Cell key={'dd_' + rowIdx + '_' + colIdx}>
                <Dropdown defaultValue={vals[colName]} options={this.state.subjects} />
            </Table.Cell>
        )
    }

    formatDate(yyyymmdd) {
        return yyyymmdd.substring(0,4) + '-' + yyyymmdd.substring(4,6) + '-' + yyyymmdd.substring(6,8);
    }

    render() {
        let dt = this.formatDate(this.state.date);
        var propsForTable = {
            data: this.state.data,
            columns: this.state.columns,
            specialCols: {
                minutes: this.displayInput,
                points: this.displayInput,
                notes: this.displayInput,
                subject: this.displayDropdown,
            }
        }
        var tbl = tableHelper.tablify(propsForTable);
        let topNavProps = JSON.parse(JSON.stringify(this.props));
        topNavProps.title = moment(dt).format('MMM D, YYYY');
        topNavProps.prevLink = '/dt/' + moment(dt).subtract(1, 'days').format('YYYYMMDD');
        topNavProps.nextLink = '/dt/' + moment(dt).add(1, 'days').format('YYYYMMDD');
        return (
            <div>
                <Grid>
                    <Grid.Row>
                        <Grid.Column width={3}>
                            <Nav date={dt} />
                        </Grid.Column>
                        <Grid.Column width={13}>
                            <Grid.Row>
                                <Grid.Column centered="true">
                                    <TopNav { ...topNavProps } />
                                </Grid.Column>
                            </Grid.Row>
                            {tbl}
                        </Grid.Column>
                    </Grid.Row>
                </Grid>
            </div>
        );
    }
}
