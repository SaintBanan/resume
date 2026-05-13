package com.example.bt.push;

import androidx.room.Entity;
import androidx.room.Ignore;
import androidx.room.Index;
import androidx.room.PrimaryKey;

@Entity(indices = { @Index(value = {"type", "data", "params"}, unique = true) })
public class PushData {
    @PrimaryKey
    private int id;
    private String type;
    private String data;
    private String params;

    @Ignore
    public PushData(String type, String data) {
        this(0, type, data, null);
    }

    @Ignore
    public PushData(String type, String data, String params) {
        this(0, type, data, params);
    }

    public PushData(int id, String type, String data, String params) {
        this.id = id;
        this.type = type;
        this.data = data;
        this.params = params;
    }

    public int getId() { return id; }

    public String getType() { return type; }

    public String getData() { return data; }

    public String getParams() { return params; }

    public void setId(int id) { this.id = id; }

    public void setType(String type) { this.type = type; }

    public void setData(String data) { this.data = data; }

    public void setParams(String params) { this.params = params; }
}
