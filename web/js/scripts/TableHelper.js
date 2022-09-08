import React from 'react'
import { Table } from 'semantic-ui-react'

const maxLength = 100

const tablify = (props) => {
    var data = props.data;
    if (!('rowFilter' in props)) {
        props.rowFilter = () => { return true; }
    }
    if (data.length === 0) {
        return (
            <Table>
                 <Table.Body>
                    <Table.Row>
                        <Table.Cell>
                            <Table.Header as='h2'>
                                NO DATA
                            </Table.Header>
                        </Table.Cell>
                    </Table.Row>
                </Table.Body>
            </Table>
        );
    }

    var columns = props.columns.filter(col => {
        return col.visible
    })

    var cols = columns.map((vals, idx) => {
        let title = vals.label;
        if ('titles' in props && vals.uid in props.titles) {
            return props.titles[vals.uid](vals);
        }
        return (
            <Table.HeaderCell key={'h_' + vals.uid}>{title}</Table.HeaderCell>
        )
    })

    var rows = data.filter(props.rowFilter).map((vals, rowIdx) => {
        var id = vals['id'];
        var cells = columns.filter((c) => { return c.uid in vals }).map((col, colIdx) => {
            if ('specialCols' in props && col.uid in props.specialCols) {
                return props.specialCols[col.uid](vals, rowIdx, colIdx)
            }
            let display = vals[col.uid]
            let maximumLen = 'maxLength' in props ? props.maxLength : maxLength
            let cb = 'cb' in props && col.uid in props.cb ? () => {props.cb[col.uid](vals)} : () => {}

            if (display && display.length > maximumLen) {
                display = display.substring(0,97) + '...'
            }

            return (
                <Table.Cell
                    key={'cell_' + rowIdx + '_' + colIdx}
                    onClick={cb}
                >
                    {display}
                </Table.Cell>);
        });

        return (
            <Table.Row key={'r_' + rowIdx}>{cells}</Table.Row>
        );
    });

    let total = (
        <Table.Row>
        </Table.Row>
    );
    if ('total' in props) {
        props.total.date = 'TOTAL';
        total = columns.map((vals, idx) => {
            let tot = props.total[vals.uid]
            if ('specialColsTot' in props && vals.uid in props.specialColsTot) {
                return props.specialColsTot[vals.uid](tot, idx);
            }
            return (
                <Table.Cell key={'tot_' + vals.uid} className='green'>{tot}</Table.Cell>
            )
        });

        total = (
            <Table.Row>
                {total}
            </Table.Row>
        )
    }



    return (
        <Table celled striped>
             <Table.Header>
                <Table.Row>{cols}</Table.Row>
             </Table.Header>
            <Table.Body>
                {rows}
                {total}
            </Table.Body>
        </Table>
    );
}

const satisfiesFilter = (row, filter) => {
    if (!Object.keys(filter).length) {
        return true;
    }

    let filterSatisfied = false

    for (var f in filter) {
        if (f === 'any') {
            if (!filter.any.length) {
                filterSatisfied = true
            } else {
                for(var k in row) {
                    if (row[k].toLowerCase().indexOf(filter.any) > -1) {
                        filterSatisfied = true
                    }
                }
            }
        } else {
            if (f === 'app') {
                if (filterSatisfied && (!filter.app.length || filter[f].indexOf(row[f].toLowerCase()) === -1)) {
                    filterSatisfied = false
                }
            } else {
                if (f === 'active') {
                    if (filterSatisfied && (!filter.active.length || filter[f].indexOf(row.status.toLowerCase()) === -1)) {
                        filterSatisfied = false
                    }
                } else {
                    if (filterSatisfied && row[f].toLowerCase().indexOf(filter[f]) === -1) {
                        filterSatisfied = false
                    }
                }
            }
        }
    }

    return filterSatisfied
}

module.exports = {
    tablify,
    satisfiesFilter,
}